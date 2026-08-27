import { initializeApp } from "firebase/app";
import { getAuth, RecaptchaVerifier, signInWithPhoneNumber } from "firebase/auth";

const firebaseConfig = {
  apiKey: "AIzaSyAUPJmo8s-4Jf0SCmrPZQIgy4hAIsrLJ6w",
  authDomain: "e-learning-system-f57a2.firebaseapp.com",
  projectId: "e-learning-system-f57a2",
  storageBucket: "e-learning-system-f57a2.firebasestorage.app",
  messagingSenderId: "422968369473",
  appId: "1:422968369473:web:15e606d4b26858d3ef41c0",
  measurementId: "G-3RK3FNS574"
};

const app = initializeApp(firebaseConfig);
export const auth = getAuth(app);
export { RecaptchaVerifier, signInWithPhoneNumber };
