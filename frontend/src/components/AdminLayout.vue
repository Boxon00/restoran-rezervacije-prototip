<template>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <nav>
        <RouterLink to="/admin" exact-active-class="active">Pregled</RouterLink>
        <RouterLink to="/admin/reservations" active-class="active">Rezervacije</RouterLink>
        <RouterLink to="/admin/restaurants" active-class="active">Restorani</RouterLink>
        <RouterLink to="/admin/tables" active-class="active">Stolovi</RouterLink>
        <RouterLink to="/admin/menus" active-class="active">Jelovnici</RouterLink>
        <RouterLink to="/admin/users" active-class="active">Korisnici</RouterLink>
        <RouterLink to="/admin/analytics" active-class="active">Analitika</RouterLink>
      </nav>
    </aside>
    <div class="admin-main">
      <slot />
    </div>
  </div>
</template>
