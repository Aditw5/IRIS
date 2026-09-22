// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
import { getMessaging, onMessage, getToken } from 'firebase/messaging';
// TODO: Add SDKs for Firebase products that you want to use


// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
  apiKey: "AIzaSyByN4u-BOYyL81NSEnu-VxaFrO_QLZsiTc",
  authDomain: "transmedic-2d192.firebaseapp.com",
  projectId: "transmedic-2d192",
  storageBucket: "transmedic-2d192.appspot.com",
  messagingSenderId: "307007009455",
  appId: "1:307007009455:web:4029a9b2bbf53a09c5da5a",
  measurementId: "G-SGSTMR4V8G"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const analytics = getAnalytics(app);
// const messaging = getMessaging(app);
// if ("serviceWorker" in navigator) {
//   navigator.serviceWorker
//     .register(
//       '/sw.js',
//       { type: 'classic' }
//     )
//     .then(async (registration) => {
//       const registrationToken = await getToken(messaging);
//       console.log('Registration token:', registrationToken);
//     });
// }
export  function messaging() {
  return getMessaging(app)
}

export async function requestNotificationPermission() {

  try {
    let registrationToken = null
    const permission = await Notification.requestPermission();
    if (permission === 'granted') {
      // Get the registration token
       registrationToken = await getToken(getMessaging(app));

      console.log('Registration token:', registrationToken);

    }
    return {
      registrationToken,
      permission
    }
    console.log('Notification permission granted:', permission);
  } catch (error) {

    console.log('Error requesting notification permission:', error);
    return null;
  }
}
// const requestNotificationPermission = async () => {
//   try {

//     const permission = await messaging.requestPermission();
//     if (permission === 'granted') {
//       // Get the registration token
//       const registrationToken = await getToken(messaging);
//       console.log('Registration token:', registrationToken);
//     }
//     console.log('Notification permission granted:', permission);
//   } catch (error) {
//     console.log('Error requesting notification permission:', error);
//   }
// };



export function firebase() {
  return {
    get: function () {

    },
    set: function (element_id: any, value: any) {
      let sigCanvas: any = document.getElementById(element_id);
      if (sigCanvas) {
        let context = sigCanvas.getContext("2d");
        context.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        let imagess = value
        let background = new Image();
        background.src = imagess
        background.onload = function () {
          context.drawImage(background, 0, 0, 160, 160);
        }
      }
    }
  }
}
