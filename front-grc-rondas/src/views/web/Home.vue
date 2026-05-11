<template>
  <div>
    <Header />
    <About />
    <Organization />
    <Impact />
<!--    <Gallery />-->
    <Contact />
    <BasesView />
    <!-- Footer -->
    <footer class="footer">
      <p>&copy; 2024 CUNARC-P. Todos los derechos reservados.</p>
    </footer>
  </div>
</template>

<script>
import Header from "@/views/web/components/Header.vue";
import About from "@/views/web/components/About.vue";
import Organization from "@/views/web/components/Organizacion.vue";
import Impact from "@/views/web/components/Impact.vue";
import Gallery from "@/views/web/components/Gallery.vue";
import Contact from "@/views/web/components/Contact.vue";
import BasesView from "@/views/web/components/BasesView.vue";

export default {
  components: { Header, About, Organization, Impact, Gallery, Contact, BasesView },
};
</script>
<style>
body {
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 0;
  line-height: 1.6;
}

.header {
  text-align: center;
  background: #004d40;
  color: white;
  padding: 20px;
}

.about, .impact, .gallery {
  padding: 20px;
  max-width: 800px;
  margin: auto;
}

.impact ul {
  list-style: none;
  padding: 0;
}

.impact li {
  background: #f4f4f4;
  margin: 5px 0;
  padding: 10px;
  border-radius: 5px;
}

.gallery .images {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
}

.gallery img {
  width: 100%;
  max-width: 200px;
  border-radius: 5px;
}

.footer {
  text-align: center;
  background: #333;
  color: white;
  padding: 10px 0;
}
</style>
