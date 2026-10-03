# IDEA Madrid · Mapa de Planes Urbanos

Tema hijo de Neve para integrar el mapa interactivo de planes urbanos de IDEA Madrid. 

El código registra el tipo de contenido `proyecto_urbano`, sus categorías y el endpoint público que consume el mapa.

## Instalación

1. Sería bueno hacer una copia de seguridad de los archivos y la base de datos del sitio que tenemos ahora en producción.
2. Descargar el ZIP de este repositorio desde GitHub.
3. Copiar el contenido del ZIP a `wp-content/themes/neve-child/`.
4. Comprobar en **Apariencia → Temas** que Neve Child está activo.
5. Comprobar que la página del mapa usa la plantilla **Mapa Problemas Urbanos**.

## Actualización mediante Git

El repositorio puede clonarse en `wp-content/themes/neve-child/` o usarse para actualizar esa carpeta desde Git.

## Archivos principales

- `functions.php`: carga de recursos, registro del tema e inicialización.
- `inc/post-types.php`: tipos de contenido, campos y endpoint REST.
- `inc/meta-boxes.php`: campos de administración de los proyectos.
- `page-templates/template-mapa-urbanos.php`: plantilla de página.
- `assets/css/mapa-urbanos.css`: estilos del mapa y sus controles.
- `assets/js/mapa-urbanos.js`: inicialización del mapa, capas y filtros.
