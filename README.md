# IDEA Madrid · Mapa de Planes Urbanos

Tema hijo de Neve que integra el mapa interactivo de planes urbanos de IDEA Madrid.
La raíz de este repositorio corresponde a la carpeta del tema `neve-child`.

## Requisitos

- WordPress con el tema padre **Neve** instalado.
- El tema hijo instalado y activo.
- Una página asignada a la plantilla **Mapa Problemas Urbanos**.
- Los proyectos y sus metadatos deben existir en la base de datos de WordPress.

El código registra el tipo de contenido `proyecto_urbano`, sus categorías y el
endpoint público que consume el mapa. Los proyectos, páginas, usuarios y otros
contenidos están en la base de datos y **no forman parte de este repositorio**.

## Instalación o actualización manual

1. Haz una copia de seguridad de los archivos y la base de datos del sitio.
2. Descarga el ZIP de este repositorio desde GitHub.
3. Copia el contenido del ZIP a `wp-content/themes/neve-child/`, manteniendo la
   estructura de carpetas y sobrescribiendo los archivos existentes.
4. Comprueba en **Apariencia → Temas** que Neve Child sigue activo.
5. Comprueba que la página del mapa usa la plantilla **Mapa Problemas Urbanos**.
6. Purga la caché del sitio y del hosting; después recarga con `Ctrl+Shift+R`.

No reemplaces el tema padre Neve ni la base de datos completa para actualizar
este tema. Si hay que trasladar proyectos que aún no existen en producción,
migra esos contenidos por separado después de hacer una copia de seguridad.

## Actualización mediante Git

El repositorio puede clonarse en `wp-content/themes/neve-child/` o usarse para
actualizar esa carpeta desde Git. Para un repositorio privado, configura antes
el acceso de GitHub en el servidor. Haz copia de seguridad y purga la caché
después de cada actualización.

## Archivos principales

- `functions.php`: carga de recursos, registro del tema e inicialización.
- `inc/post-types.php`: tipos de contenido, campos y endpoint REST.
- `inc/meta-boxes.php`: campos de administración de los proyectos.
- `page-templates/template-mapa-urbanos.php`: plantilla de página.
- `assets/css/mapa-urbanos.css`: estilos del mapa y sus controles.
- `assets/js/mapa-urbanos.js`: inicialización del mapa, capas y filtros.
