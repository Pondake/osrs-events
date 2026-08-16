<script setup lang="ts">
definePageMeta({ middleware: ['admin'] });
await navigateTo('/admin/boards', { replace: true });
</script>
