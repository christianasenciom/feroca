<template>
  <div class="error-page">
    <h1>404 Error</h1>
    <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 24 24">
      <path
        fill="#000000"
        d="M12,2C6.48,2 2,6.48 2,12C2,17.52 6.48,22 12,22C17.52,22 22,17.52 22,12C22,6.48 17.52,2 12,2M12,4C16.41,4 20,7.59 20,12C20,16.41 16.41,20 12,20C7.59,20 4,16.41 4,12C4,7.59 7.59,4 12,4M11,7H13V9H11V7M11,11H13V17H11V11Z"
      />
    </svg>
    <p>El QR no es válido.</p>
  </div>
</template>

<script setup></script>

<style scoped>
.error-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  text-align: center;
}

h1 {
  font-size: 48px;
  margin-bottom: 20px;
}

svg {
  width: 150px;
  height: 150px;
  margin-bottom: 20px;
}

p {
  font-size: 18px;
  margin-bottom: 20px;
}

.btn {
  padding: 10px 20px;
  background-color: #007bff;
  color: #fff;
  text-decoration: none;
  border-radius: 5px;
  font-size: 16px;
}

.btn:hover {
  background-color: #0056b3;
}
</style>
