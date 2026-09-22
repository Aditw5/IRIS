const fs = require('fs')
const path = require('path')
const assert = require('assert/strict')
const { parse, compileScript, compileTemplate } = require('@vue/compiler-sfc')
const ts = require('typescript')
const vm = require('vm')
const vue = require('vue')
const root = path.join(__dirname, '..')
let pages = 0
const files = [
  'src/components/worksheet/WorksheetInstructionPicker.vue',
  'src/components/worksheet/WorksheetMasterButton.vue',
  'src/components/partials/lembar-kerja/WorksheetMetadataCard.vue',
  'src/pages/module/sysadmin/master-instruksi-kerja.vue',
]
for (const role of ['pelaksana', 'penyelia']) {
  const dir = `src/pages/module/${role}`
  for (const file of fs.readdirSync(path.join(root, dir)).filter(name => /^lembar-kerja.*\.vue$/.test(name))) {
    const source = fs.readFileSync(path.join(root, dir, file), 'utf8')
    if (!source.includes('items.daftarInstruksiKerja')) continue
    assert(source.includes('<WorksheetInstructionPicker v-model="items.daftarInstruksiKerja" />'), file)
    assert(source.includes('<WorksheetMasterButton kind="ik" />'), file)
    assert(source.includes('<WorksheetMasterButton kind="standar" />'), file)
    assert(source.includes('<div class="column is-6">\n                <Fieldset legend="- Instruksi Kerja"'), `${file}: IK column must be 6`)
    assert(source.includes('<div class="column is-6">\n                <Fieldset legend="- Daftar Peralatan Standar"'), `${file}: standard column must be 6`)
    assert(!source.includes('general/dropdown/instruksikerja_m'), file)
    files.push(`${dir}/${file}`)
    pages++
  }
}
for (const filename of files) {
  const source = fs.readFileSync(path.join(root, filename), 'utf8')
  const { descriptor, errors } = parse(source, { filename })
  assert.equal(errors.length, 0, filename)
  const script = compileScript(descriptor, { id: filename })
  const compiled = compileTemplate({ source: descriptor.template.content, filename, id: filename, compilerOptions: { bindingMetadata: script.bindings } })
  assert.equal(compiled.errors.length, 0, `${filename}: ${compiled.errors}`)
  const result = ts.transpileModule(script.content, { compilerOptions: { target: ts.ScriptTarget.ES2020 }, reportDiagnostics: true })
  assert.equal((result.diagnostics || []).length, 0, filename)
}
console.log(`PASS: ${pages} worksheets have the guarded picker and both management buttons; ${files.length} SFCs compile.`)
const metadataSource = fs.readFileSync(path.join(root, 'src/components/partials/lembar-kerja/WorksheetMetadataCard.vue'), 'utf8')
assert(metadataSource.includes('<WorksheetInstructionPicker v-model="row.daftarInstruksiKerja" />'))
assert(!metadataSource.includes('general/dropdown/instruksikerja_m'), 'Metadata editor must use the guarded picker too')
const pickerSource = fs.readFileSync(path.join(root, 'src/components/worksheet/WorksheetInstructionPicker.vue'), 'utf8')
assert(pickerSource.includes('@dropdown-click="openDropdown"'), 'Opening the dropdown must fetch current availability')
assert(!pickerSource.includes('Perbarui daftar IK'), 'The picker must not look manually refreshed')
assert(!pickerSource.includes('Hanya IK yang sudah memiliki file'), 'The permanent helper text must stay hidden')
assert(pickerSource.includes('v-if="option.disabled && option.reason"'), 'Availability text must only render for unavailable IK')
const buttonSource = fs.readFileSync(path.join(root, 'src/components/worksheet/WorksheetMasterButton.vue'), 'utf8')
assert(!buttonSource.includes('Kelola data dan upload file IK'))
assert(!buttonSource.includes('Kelola data peralatan standar'))

async function checkRefreshLifecycle() {
  const requests = []
  const windowEvents = new EventTarget()
  const documentEvents = new EventTarget()
  documentEvents.hidden = false
  const timers = new Set()
  const module = { exports: {} }
  const source = fs.readFileSync(path.join(root, 'src/composable/useWorksheetInstructions.ts'), 'utf8')
  const javascript = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2020 } }).outputText
  vm.runInNewContext(javascript, {
    module, exports: module.exports, Event,
    window: windowEvents, document: documentEvents,
    localStorage: { setItem() {} },
    Date,
    setInterval(fn) { timers.add(fn); return fn },
    clearInterval(fn) { timers.delete(fn) },
    require(name) {
      if (name === 'vue') return vue
      return { useApi: () => ({ get: (url, config) => new Promise((resolve, reject) => requests.push({ url, config, resolve, reject })) }) }
    },
  })
  const { useWorksheetInstructions, notifyWorksheetInstructionsChanged } = module.exports
  const renderer = vue.createRenderer({
    createElement: () => ({}), createText: () => ({}), createComment: () => ({}),
    insert() {}, remove() {}, setText() {}, setElementText() {}, patchProp() {},
    parentNode() { return null }, nextSibling() { return null },
  })
  const mount = () => {
    let state
    const app = renderer.createApp({ setup() { state = useWorksheetInstructions(); return () => null } })
    app.mount({})
    return { app, state }
  }
  const flush = async () => { await Promise.resolve(); await Promise.resolve(); await vue.nextTick() }
  const first = mount()
  const second = mount()
  assert.equal(requests.length, 1, 'Rows share one in-flight lookup')
  assert(requests[0].url.includes('/sysadmin/worksheet-instructions?_refresh='))
  assert.equal(requests[0].config.timeout, 15000, 'A stalled lookup must time out instead of loading forever')
  requests[0].resolve([{ value: 1, disabled: true }])
  await flush()
  assert.equal(first.state.options.value[0].disabled, true)
  notifyWorksheetInstructionsChanged()
  requests[1].resolve([{ value: 1, disabled: false }])
  await flush()
  assert.equal(second.state.options.value[0].disabled, false, 'Upload immediately refreshes all rows')

  first.app.unmount()
  windowEvents.dispatchEvent(new Event('focus'))
  assert.equal(requests.length, 3, 'Focus refresh survives removing the first row')
  notifyWorksheetInstructionsChanged()
  assert.equal(requests.length, 4, 'Upload bypasses an older pending request')
  requests[3].resolve([{ value: 1, disabled: false, fileCount: 2 }])
  await flush()
  requests[2].resolve([{ value: 1, disabled: true }])
  await flush()
  assert.equal(second.state.options.value[0].fileCount, 2, 'Late stale responses cannot replace fresh availability')

  const storage = new Event('storage')
  storage.key = 'worksheet-instructions-changed'
  windowEvents.dispatchEvent(storage)
  assert.equal(requests.length, 5, 'Uploads in another tab refresh the worksheet')
  requests[4].reject(new Error('offline'))
  await flush()
  assert(second.state.error.value, 'An unavailable backend reports a blocking status')
  for (const timer of timers) timer()
  requests[5].resolve([{ value: 1, disabled: false }])
  await flush()
  assert.equal(second.state.error.value, '', 'Polling recovers after a failed request')
  documentEvents.hidden = true
  for (const timer of timers) timer()
  assert.equal(requests.length, 6, 'Hidden worksheets do not poll')
  second.app.unmount()
  assert.equal(timers.size, 0, 'Timers are removed on navigation')
  windowEvents.dispatchEvent(new Event('focus'))
  assert.equal(requests.length, 6, 'Listeners are removed on navigation')
  console.log('PASS: upload events, cross-tab changes, focus refresh, polling, stale-response protection, recovery and cleanup.')
}
checkRefreshLifecycle().catch(error => { console.error(error); process.exitCode = 1 })

function checkLegacySelection() {
  const filename = 'src/components/worksheet/WorksheetInstructionPicker.vue'
  const { descriptor } = parse(fs.readFileSync(path.join(root, filename), 'utf8'), { filename })
  const script = compileScript(descriptor, { id: 'picker-regression' })
  const javascript = ts.transpileModule(script.content, { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2020 } }).outputText
  const module = { exports: {} }
  const options = vue.ref([
    { value: 1, label: 'Old certificate IK', active: true, disabled: true, reason: 'Upload file IK terlebih dahulu' },
    { value: 2, label: 'New IK without file', active: true, disabled: true },
    { value: 3, label: 'Uploaded IK', active: true, disabled: false },
  ])
  vm.runInNewContext(javascript, {
    module, exports: module.exports,
    require(name) {
      if (name === 'vue') return vue
      if (name === 'primevue/autocomplete') return {}
      return { useWorksheetInstructions: () => ({ options, loading: vue.ref(false), error: vue.ref(''), refresh: async () => {} }) }
    },
  })
  const props = vue.reactive({ modelValue: { value: 1, label: 'Original certificate label' } })
  const events = []
  let state
  const renderer = vue.createRenderer({
    createElement: () => ({}), createText: () => ({}), createComment: () => ({}),
    insert() {}, remove() {}, setText() {}, setElementText() {}, patchProp() {},
    parentNode() { return null }, nextSibling() { return null },
  })
  const app = renderer.createApp({ setup() {
    state = module.exports.default.setup(props, { expose() {}, emit: (...args) => events.push(args) })
    return () => null
  } })
  app.mount({})
  for (const searchText of ['new search', '', null]) {
    state.update(searchText)
    state.finishSearch()
    assert.equal(state.inputValue.value.value, 1, 'Typing/blurring must preserve the saved IK')
    assert.equal(events.length, 0)
  }
  state.update(options.value[1])
  assert.equal(events.length, 0, 'A replacement without a file must be rejected')
  state.update(options.value[2])
  assert.equal(events.length, 1)
  assert.equal(events[0][1].value, 3, 'Only an uploaded IK can replace the saved reference')
  assert.equal(props.modelValue.label, 'Original certificate label')
  app.unmount()
  console.log('PASS: legacy selection survives searching/blur; unavailable replacements are blocked; uploaded replacements are accepted.')
}
checkLegacySelection()
