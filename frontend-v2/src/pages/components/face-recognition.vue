<script setup>
import { ref, onMounted } from "vue";
import * as faceapi from "face-api.js";

const video = ref(null);
const isRecognizing = ref(false);
const message = ref("Arahkan wajah ke kamera");

onMounted(async () => {
  await loadModels();
  startCamera();
});

async function loadModels() {
    await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
    await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
    await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
    await faceapi.nets.faceExpressionNet.loadFromUri('/models');
}

async function detectFace() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const displaySize = { width: video.width, height: video.height };

    faceapi.matchDimensions(canvas, displaySize);

    setInterval(async () => {
        const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceExpressions();

        const resizedDetections = faceapi.resizeResults(detections, displaySize);
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        faceapi.draw.drawDetections(canvas, resizedDetections);
        faceapi.draw.drawFaceLandmarks(canvas, resizedDetections);
        faceapi.draw.drawFaceExpressions(canvas, resizedDetections);
    }, 100);
}

const startCamera = async () => {
  const stream = await navigator.mediaDevices.getUserMedia({ video: true });
  if (video.value) video.value.srcObject = stream;
};

const captureFace = async () => {
  if (!video.value) return;
  isRecognizing.value = true;

  const detections = await faceapi
    .detectSingleFace(video.value)
    .withFaceLandmarks()
    .withFaceDescriptor();

  if (detections) {
    console.log("Wajah terdeteksi!", detections);
    // Kirim data ke backend untuk validasi
    message.value = "Wajah dikenali!";
  } else {
    console.log("Wajah tidak dikenali.");
    message.value = "Wajah tidak dikenali.";
  }

  isRecognizing.value = false;
};
</script>

<template>
  <div class="container">
    <video ref="video" autoplay playsinline></video>
    <p>{{ message }}</p>
    <button @click="captureFace" :disabled="isRecognizing">
      Scan Wajah
    </button>
  </div>
</template>

<style scoped>
.container {
  text-align: center;
}
video {
  width: 100%;
  max-width: 400px;
  border-radius: 10px;
  margin-bottom: 10px;
}
button {
  padding: 10px 15px;
  background: #007bff;
  color: white;
  border: none;
  cursor: pointer;
}
</style>
