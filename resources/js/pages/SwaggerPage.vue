<template>
    <DashboardLayout>
      <div id="swagger-ui"></div>
    </DashboardLayout>
</template>

<script setup lang="ts">
  import SwaggerUI from 'swagger-ui';
  import 'swagger-ui/dist/swagger-ui.css';
  import { onMounted } from 'vue';
  import DashboardLayout from '@/layouts/DashboardLayout.vue';

  onMounted(() => {
      SwaggerUI({
          dom_id: '#swagger-ui',
          url: '/docs/api-docs.json',
      });
  });
</script>
