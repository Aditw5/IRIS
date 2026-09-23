import * as THREE from 'three'
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js'

// Kept in a separate, lazy-loaded chunk: the page remains usable without WebGL.
export function createIrisScene(host, { onReady, onFailure, paused = false }) {
  const scene = new THREE.Scene()
  const camera = new THREE.PerspectiveCamera(36, 1, 0.1, 60)
  camera.position.set(0, 0, 10.7)
  const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: 'low-power' })
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.6))
  renderer.setClearColor(0x000000, 0)
  renderer.toneMapping = THREE.ACESFilmicToneMapping
  renderer.toneMappingExposure = 1
  renderer.outputColorSpace = THREE.SRGBColorSpace
  renderer.domElement.setAttribute('aria-hidden', 'true')
  host.appendChild(renderer.domElement)

  const pmrem = new THREE.PMREMGenerator(renderer)
  const room = new RoomEnvironment()
  const environment = pmrem.fromScene(room, 0.04)
  scene.environment = environment.texture
  room.dispose()
  pmrem.dispose()
  scene.add(new THREE.HemisphereLight(0xeaffff, 0x164780, 2.2))
  const key = new THREE.DirectionalLight(0xffffff, 4)
  key.position.set(-3, 5, 8)
  scene.add(key)
  const blueLight = new THREE.PointLight(0x00baff, 35, 15)
  blueLight.position.set(4, -2, 4)
  scene.add(blueLight)

  const sculpture = new THREE.Group()
  sculpture.rotation.set(0.17, -0.4, -0.18)
  scene.add(sculpture)
  const iris = new THREE.Group()
  sculpture.add(iris)
  const cyan = new THREE.MeshPhysicalMaterial({
    color: 0x00acde,
    metalness: 0.45,
    roughness: 0.22,
    envMapIntensity: 0.75,
    clearcoat: 1,
    clearcoatRoughness: 0.12,
  })
  const pale = new THREE.MeshPhysicalMaterial({
    color: 0x92e8fa,
    metalness: 0.36,
    roughness: 0.2,
    clearcoat: 1,
  })
  const navy = new THREE.MeshPhysicalMaterial({
    color: 0x082b59,
    metalness: 0.45,
    roughness: 0.3,
    clearcoat: 1,
  })
  const silver = new THREE.MeshPhysicalMaterial({ color: 0xd4f1ff, metalness: 0.8, roughness: 0.19 })

  // Four extruded sectors echo the supplied IRIS mark, not a generic torus.
  const sectors = [
    [1.93, 6.3],
    [0.12, 0.63],
    [0.76, 1.23],
    [1.36, 1.79],
  ]
  const segments = sectors.map(([start, end], index) => {
    const outer = 1.95
    const inner = 1.03
    const shape = new THREE.Shape()
    shape.moveTo(Math.cos(start) * outer, Math.sin(start) * outer)
    shape.absarc(0, 0, outer, start, end, false)
    shape.lineTo(Math.cos(end) * inner, Math.sin(end) * inner)
    shape.absarc(0, 0, inner, end, start, true)
    shape.closePath()
    const geometry = new THREE.ExtrudeGeometry(shape, {
      depth: 0.31,
      bevelEnabled: true,
      bevelSegments: 4,
      steps: 1,
      bevelSize: 0.055,
      bevelThickness: 0.055,
      curveSegments: 64,
    })
    geometry.translate(0, 0, -0.16)
    const mesh = new THREE.Mesh(geometry, index === 2 ? pale : cyan)
    mesh.userData.angle = (start + end) / 2
    iris.add(mesh)
    return mesh
  })
  const core = new THREE.Mesh(new THREE.SphereGeometry(0.62, 48, 32), navy)
  core.scale.z = 0.5
  core.position.z = 0.13
  iris.add(core)
  const collar = new THREE.Mesh(new THREE.TorusGeometry(0.74, 0.045, 12, 100), silver)
  collar.position.z = 0.02
  iris.add(collar)

  const orbit = new THREE.Group()
  sculpture.add(orbit)
  const orbitMaterial = new THREE.MeshBasicMaterial({ color: 0x83c9e2, transparent: true, opacity: 0.4 })
  const hoops = [2.48, 2.84, 3.17].map((radius, index) => {
    const hoop = new THREE.Mesh(
      new THREE.TorusGeometry(radius, index === 1 ? 0.008 : 0.006, 6, 160),
      orbitMaterial
    )
    hoop.rotation.set(index * 0.21, index * -0.12, 0)
    orbit.add(hoop)
    return hoop
  })
  const satellites = [0, 1, 2].map((index) => {
    const dot = new THREE.Mesh(
      new THREE.SphereGeometry(index === 0 ? 0.075 : 0.047, 14, 14),
      index === 0 ? cyan : silver
    )
    orbit.add(dot)
    return dot
  })
  const ticks = new THREE.Group()
  for (let i = 0; i < 80; i++) {
    const angle = (i / 80) * Math.PI * 2
    const tick = new THREE.Mesh(
      new THREE.BoxGeometry(0.006, i % 5 === 0 ? 0.08 : 0.033, 0.004),
      orbitMaterial
    )
    tick.position.set(Math.cos(angle) * 2.19, Math.sin(angle) * 2.19, -0.1)
    tick.rotation.z = angle - Math.PI / 2
    ticks.add(tick)
  }
  sculpture.add(ticks)

  const positions = new Float32Array(55 * 3)
  for (let i = 0; i < 55; i++) {
    // Deterministic distribution keeps the composition stable across renders.
    positions[i * 3] = Math.sin(i * 127.1) * 4
    positions[i * 3 + 1] = Math.cos(i * 311.7) * 3.5
    positions[i * 3 + 2] = Math.sin(i * 74.7) * 2 - 2
  }
  const dustGeometry = new THREE.BufferGeometry()
  dustGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3))
  const dust = new THREE.Points(
    dustGeometry,
    new THREE.PointsMaterial({ color: 0x44b5e4, size: 0.025, transparent: true, opacity: 0.5 })
  )
  scene.add(dust)

  let frame = 0
  let time = 0
  let previousTime = 0
  let inView = true
  let isPaused = paused
  let disposed = false
  let contextLost = false
  let scrollOffset = 0
  const pointer = { x: 0, y: 0 }
  function render() {
    if (!disposed && !contextLost) renderer.render(scene, camera)
  }
  function animate(now) {
    frame = 0
    if (disposed || isPaused || !inView || document.hidden || contextLost) return
    const delta = previousTime ? Math.min((now - previousTime) / 1000, 0.05) : 0
    previousTime = now
    time += delta
    // A quicker, frame-rate-independent ease keeps cursor tracking responsive.
    const pointerResponse = 1 - Math.exp(-3.8 * delta)
    sculpture.rotation.y = THREE.MathUtils.lerp(
      sculpture.rotation.y,
      -0.35 + pointer.x * 0.45 + scrollOffset * 0.15,
      pointerResponse
    )
    sculpture.rotation.x = THREE.MathUtils.lerp(
      sculpture.rotation.x,
      0.16 + pointer.y * 0.3,
      pointerResponse
    )
    sculpture.rotation.z = -0.16 + Math.sin(time * 0.22) * 0.08
    sculpture.position.y = Math.sin(time * 0.65) * 0.065
    segments.forEach((segment, index) => {
      const expansion = 0.015 + (Math.sin(time * 0.8 + index * 0.3) + 1) * 0.025
      segment.position.x = Math.cos(segment.userData.angle) * expansion
      segment.position.y = Math.sin(segment.userData.angle) * expansion
      segment.position.z = Math.sin(time * 0.6 + index) * 0.055
    })
    satellites.forEach((satellite, index) => {
      const angle = time * (0.12 + index * 0.025) + index * 2.2
      const radius = 2.48 + index * 0.35
      satellite.position.set(
        Math.cos(angle) * radius,
        Math.sin(angle) * radius,
        Math.sin(angle) * index * 0.3
      )
    })
    hoops[1].rotation.z = time * 0.015
    ticks.rotation.z = -time * 0.018
    dust.rotation.z = time * 0.008
    render()
    frame = requestAnimationFrame(animate)
  }
  function updatePlayback() {
    cancelAnimationFrame(frame)
    frame = 0
    previousTime = 0
    if (!disposed && !isPaused && inView && !document.hidden && !contextLost)
      frame = requestAnimationFrame(animate)
    else render()
  }
  function resize() {
    const { width, height } = host.getBoundingClientRect()
    if (!width || !height) return
    renderer.setSize(width, height)
    camera.aspect = width / height
    camera.position.z = camera.aspect < 0.85 ? 12.2 : 10.7
    camera.updateProjectionMatrix()
    render()
  }
  function onPointer(event) {
    const rect = host.getBoundingClientRect()
    pointer.x = THREE.MathUtils.clamp((event.clientX - rect.left) / rect.width - 0.5, -0.5, 0.5)
    pointer.y = THREE.MathUtils.clamp((event.clientY - rect.top) / rect.height - 0.5, -0.5, 0.5)
  }
  function onLeave() {
    pointer.x = 0
    pointer.y = 0
  }
  function onScroll() {
    scrollOffset = Math.min(window.scrollY / window.innerHeight, 1)
  }
  function onContextLost(event) {
    event.preventDefault()
    contextLost = true
    cancelAnimationFrame(frame)
    onFailure()
  }
  function onContextRestored() {
    contextLost = false
    resize()
    onReady()
    updatePlayback()
  }
  const resizeObserver = new ResizeObserver(resize)
  resizeObserver.observe(host)
  const visibilityObserver = new IntersectionObserver(
    ([entry]) => {
      inView = entry.isIntersecting
      updatePlayback()
    },
    { rootMargin: '60px' }
  )
  visibilityObserver.observe(host)
  host.addEventListener('pointermove', onPointer, { passive: true })
  host.addEventListener('pointerleave', onLeave)
  window.addEventListener('scroll', onScroll, { passive: true })
  document.addEventListener('visibilitychange', updatePlayback)
  renderer.domElement.addEventListener('webglcontextlost', onContextLost)
  renderer.domElement.addEventListener('webglcontextrestored', onContextRestored)
  resize()
  // Position orbit markers even when the initial preference is reduced motion.
  satellites.forEach((satellite, index) =>
    satellite.position.set(
      Math.cos(index * 2.2) * (2.48 + index * 0.35),
      Math.sin(index * 2.2) * (2.48 + index * 0.35),
      0
    )
  )
  render()
  onReady()
  updatePlayback()
  return {
    setPaused(value) {
      isPaused = value
      updatePlayback()
    },
    dispose() {
      disposed = true
      cancelAnimationFrame(frame)
      resizeObserver.disconnect()
      visibilityObserver.disconnect()
      host.removeEventListener('pointermove', onPointer)
      host.removeEventListener('pointerleave', onLeave)
      window.removeEventListener('scroll', onScroll)
      document.removeEventListener('visibilitychange', updatePlayback)
      renderer.domElement.removeEventListener('webglcontextlost', onContextLost)
      renderer.domElement.removeEventListener('webglcontextrestored', onContextRestored)
      const geometries = new Set()
      const materials = new Set()
      scene.traverse((object) => {
        if (object.geometry) geometries.add(object.geometry)
        if (object.material)
          (Array.isArray(object.material) ? object.material : [object.material]).forEach((material) =>
            materials.add(material)
          )
      })
      geometries.forEach((geometry) => geometry.dispose())
      materials.forEach((material) => material.dispose())
      environment.dispose()
      renderer.dispose()
      renderer.forceContextLoss()
      renderer.domElement.remove()
    },
  }
}
