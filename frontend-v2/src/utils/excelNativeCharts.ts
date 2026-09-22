import { strFromU8, strToU8, unzipSync, zipSync } from 'fflate'

export type NativeExcelChartSeries = {
  name: string
  titleCell: string
  valueRange: string
  values: number[]
  color: string
}

export type NativeExcelChart = {
  title: string
  categoryRange: string
  categories: string[]
  series: NativeExcelChartSeries[]
  from: { col: number; row: number }
  to: { col: number; row: number }
  horizontal?: boolean
  showValues?: boolean
  valueFormat?: string
}

const XML_HEADER = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
const CHART_NS = 'http://schemas.openxmlformats.org/drawingml/2006/chart'
const DRAWING_NS = 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing'
const DRAWINGML_NS = 'http://schemas.openxmlformats.org/drawingml/2006/main'
const OFFICE_REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
const PACKAGE_REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships'

function escapeXml(value: unknown) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;')
}

function sheetFormulaName(sheetName: string) {
  return `'${sheetName.replace(/'/g, "''")}'`
}

function stringCache(values: string[]) {
  return `<c:strCache><c:ptCount val="${values.length}"/>${values
    .map((value, index) => `<c:pt idx="${index}"><c:v>${escapeXml(value)}</c:v></c:pt>`)
    .join('')}</c:strCache>`
}

function numberCache(values: number[], formatCode: string) {
  return `<c:numCache><c:formatCode>${escapeXml(formatCode)}</c:formatCode><c:ptCount val="${values.length}"/>${values
    .map((value, index) => `<c:pt idx="${index}"><c:v>${Number(value || 0)}</c:v></c:pt>`)
    .join('')}</c:numCache>`
}

function chartTitleXml(title: string) {
  return `<c:title><c:tx><c:rich><a:bodyPr/><a:lstStyle/><a:p><a:r><a:rPr lang="id-ID" sz="1200" b="1"/><a:t>${escapeXml(title)}</a:t></a:r></a:p></c:rich></c:tx><c:layout/><c:overlay val="0"/></c:title>`
}

function chartSeriesXml(
  chart: NativeExcelChart,
  series: NativeExcelChartSeries,
  index: number,
  sheetName: string
) {
  const sheet = sheetFormulaName(sheetName)
  const valueFormat = chart.valueFormat || 'General'
  const color = series.color.replace('#', '').toUpperCase()

  return `<c:ser><c:idx val="${index}"/><c:order val="${index}"/><c:tx><c:strRef><c:f>${sheet}!${series.titleCell}</c:f>${stringCache([series.name])}</c:strRef></c:tx><c:spPr><a:solidFill><a:srgbClr val="${color}"/></a:solidFill><a:ln><a:noFill/></a:ln></c:spPr><c:invertIfNegative val="0"/><c:cat><c:strRef><c:f>${sheet}!${chart.categoryRange}</c:f>${stringCache(chart.categories)}</c:strRef></c:cat><c:val><c:numRef><c:f>${sheet}!${series.valueRange}</c:f>${numberCache(series.values, valueFormat)}</c:numRef></c:val></c:ser>`
}

function chartXml(chart: NativeExcelChart, chartIndex: number, sheetName: string) {
  const categoryAxisId = 100000 + chartIndex * 2
  const valueAxisId = categoryAxisId + 1
  const horizontal = Boolean(chart.horizontal)
  const categoryAxisPosition = horizontal ? 'l' : 'b'
  const valueAxisPosition = horizontal ? 'b' : 'l'
  const valueFormat = chart.valueFormat || 'General'
  const legend = chart.series.length > 1
    ? '<c:legend><c:legendPos val="b"/><c:layout/><c:overlay val="0"/></c:legend>'
    : ''

  return `${XML_HEADER}<c:chartSpace xmlns:c="${CHART_NS}" xmlns:a="${DRAWINGML_NS}" xmlns:r="${OFFICE_REL_NS}"><c:date1904 val="0"/><c:lang val="id-ID"/><c:roundedCorners val="0"/><c:chart>${chartTitleXml(chart.title)}<c:autoTitleDeleted val="0"/><c:plotArea><c:layout/><c:barChart><c:barDir val="${horizontal ? 'bar' : 'col'}"/><c:grouping val="clustered"/><c:varyColors val="${chart.series.length === 1 ? '1' : '0'}"/>${chart.series
    .map((series, index) => chartSeriesXml(chart, series, index, sheetName))
    .join('')}<c:dLbls><c:showLegendKey val="0"/><c:showVal val="${chart.showValues ? '1' : '0'}"/><c:showCatName val="0"/><c:showSerName val="0"/><c:showPercent val="0"/><c:showBubbleSize val="0"/></c:dLbls><c:gapWidth val="${horizontal ? '65' : '90'}"/><c:overlap val="0"/><c:axId val="${categoryAxisId}"/><c:axId val="${valueAxisId}"/></c:barChart><c:catAx><c:axId val="${categoryAxisId}"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="${categoryAxisPosition}"/><c:tickLblPos val="nextTo"/><c:crossAx val="${valueAxisId}"/><c:crosses val="autoZero"/><c:auto val="1"/><c:lblAlgn val="ctr"/><c:lblOffset val="100"/></c:catAx><c:valAx><c:axId val="${valueAxisId}"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="${valueAxisPosition}"/><c:numFmt formatCode="${escapeXml(valueFormat)}" sourceLinked="0"/><c:majorGridlines/><c:tickLblPos val="nextTo"/><c:crossAx val="${categoryAxisId}"/><c:crosses val="autoZero"/><c:crossBetween val="between"/></c:valAx></c:plotArea>${legend}<c:plotVisOnly val="0"/><c:dispBlanksAs val="zero"/><c:showDLblsOverMax val="0"/></c:chart><c:printSettings><c:headerFooter/><c:pageMargins b="0.75" l="0.7" r="0.7" t="0.75" header="0.3" footer="0.3"/><c:pageSetup/></c:printSettings></c:chartSpace>`
}

function drawingAnchor(chart: NativeExcelChart, index: number) {
  const id = index + 2
  return `<xdr:twoCellAnchor><xdr:from><xdr:col>${chart.from.col}</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>${chart.from.row}</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from><xdr:to><xdr:col>${chart.to.col}</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>${chart.to.row}</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to><xdr:graphicFrame macro=""><xdr:nvGraphicFramePr><xdr:cNvPr id="${id}" name="${escapeXml(chart.title)}"/><xdr:cNvGraphicFramePr/></xdr:nvGraphicFramePr><xdr:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/></xdr:xfrm><a:graphic><a:graphicData uri="${CHART_NS}"><c:chart xmlns:c="${CHART_NS}" xmlns:r="${OFFICE_REL_NS}" r:id="rId${index + 1}"/></a:graphicData></a:graphic></xdr:graphicFrame><xdr:clientData/></xdr:twoCellAnchor>`
}

function appendContentType(contentTypes: string, partName: string, contentType: string) {
  if (contentTypes.includes(`PartName="${partName}"`)) return contentTypes
  return contentTypes.replace(
    '</Types>',
    `<Override PartName="${partName}" ContentType="${contentType}"/></Types>`
  )
}

function nextRelationshipId(relationships: string) {
  const ids = Array.from(relationships.matchAll(/Id="rId(\d+)"/g))
    .map((match) => Number(match[1]))
    .filter(Number.isFinite)
  return `rId${(ids.length ? Math.max(...ids) : 0) + 1}`
}

export function addNativeExcelCharts(
  source: ArrayBuffer | Uint8Array,
  sheetName: string,
  charts: NativeExcelChart[]
) {
  if (!charts.length) return source instanceof Uint8Array ? source : new Uint8Array(source)

  const files = unzipSync(source instanceof Uint8Array ? source : new Uint8Array(source))
  const sheetPath = 'xl/worksheets/sheet1.xml'
  const sheetRelationshipsPath = 'xl/worksheets/_rels/sheet1.xml.rels'
  const drawingPath = 'xl/drawings/drawing1.xml'
  const drawingRelationshipsPath = 'xl/drawings/_rels/drawing1.xml.rels'

  let sheetXml = strFromU8(files[sheetPath])
  let sheetRelationships = files[sheetRelationshipsPath]
    ? strFromU8(files[sheetRelationshipsPath])
    : `${XML_HEADER}<Relationships xmlns="${PACKAGE_REL_NS}"></Relationships>`
  const drawingRelationshipId = nextRelationshipId(sheetRelationships)

  sheetRelationships = sheetRelationships.replace(
    '</Relationships>',
    `<Relationship Id="${drawingRelationshipId}" Type="${OFFICE_REL_NS}/drawing" Target="../drawings/drawing1.xml"/></Relationships>`
  )
  sheetXml = sheetXml.replace('</worksheet>', `<drawing r:id="${drawingRelationshipId}"/></worksheet>`)

  const drawingXml = `${XML_HEADER}<xdr:wsDr xmlns:xdr="${DRAWING_NS}" xmlns:a="${DRAWINGML_NS}">${charts
    .map(drawingAnchor)
    .join('')}</xdr:wsDr>`
  const drawingRelationships = `${XML_HEADER}<Relationships xmlns="${PACKAGE_REL_NS}">${charts
    .map((_, index) => `<Relationship Id="rId${index + 1}" Type="${OFFICE_REL_NS}/chart" Target="../charts/chart${index + 1}.xml"/>`)
    .join('')}</Relationships>`

  let contentTypes = strFromU8(files['[Content_Types].xml'])
  contentTypes = appendContentType(
    contentTypes,
    '/xl/drawings/drawing1.xml',
    'application/vnd.openxmlformats-officedocument.drawing+xml'
  )

  charts.forEach((chart, index) => {
    const chartNumber = index + 1
    files[`xl/charts/chart${chartNumber}.xml`] = strToU8(chartXml(chart, chartNumber, sheetName))
    contentTypes = appendContentType(
      contentTypes,
      `/xl/charts/chart${chartNumber}.xml`,
      'application/vnd.openxmlformats-officedocument.drawingml.chart+xml'
    )
  })

  files[sheetPath] = strToU8(sheetXml)
  files[sheetRelationshipsPath] = strToU8(sheetRelationships)
  files[drawingPath] = strToU8(drawingXml)
  files[drawingRelationshipsPath] = strToU8(drawingRelationships)
  files['[Content_Types].xml'] = strToU8(contentTypes)

  return zipSync(files, { level: 6 })
}

export function downloadExcelFile(bytes: Uint8Array, fileName: string) {
  const blob = new Blob(
    [bytes],
    { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }
  )
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = fileName
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}
