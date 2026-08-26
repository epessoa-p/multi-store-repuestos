**Para:** [ Nombre del cliente ]
**De:** [ Eric Pessoa Montaño ]
**Fecha:** [ 24/08/2026 ]
**Cotización N°:** [ 003 ]

---

## Objetivo

Incorporar el **origen (procedencia) de los productos** al sistema —para saber si un repuesto es brasilero, chino, japonés, etc.— con su administración, asignación por producto, filtros en el Punto de Venta e inventario, e integración con la importación masiva. Incluye además mejoras de usabilidad en el POS y en el catálogo de categorías.

---

## Detalle de los desarrollos

### 1. Módulo "Origen" (procedencia) de productos
Nuevo catálogo para clasificar los productos por su país de procedencia.

Incluye:
- Nueva estructura en base de datos (tabla de orígenes + campo en productos) con su **script SQL de producción**.
- Sección de administración **"Orígenes"** (crear, editar, eliminar) con **permisos por rol**, asignables desde la vista de Cargos.
- Campo **"Origen"** en el alta y edición de productos.
- Carga inicial de orígenes comunes (Brasil, China, Japón).

- **Tipo:** Funcionalidad nueva
- **Horas:** 6
- **Subtotal:** Bs 180,00

### 2. Origen en el POS y en los listados de inventario
Incluye:
- **Filtro por origen** en el Punto de Venta (POS).
- Columna **"Origen"** con **edición directa (inline, sin recargar)** y **filtro** en el listado de productos.
- Dato de origen y **filtro por origen** en el listado de inventario (stock).

- **Tipo:** Funcionalidad nueva
- **Horas:** 4
- **Subtotal:** Bs 120,00

### 3. Origen en la importación de inventario
Incluye:
- Columna **"Origen"** en la plantilla de importación descargable.
- Columna de origen en los **pasos de revisión** previos a importar.
- Guardado inteligente: si la casilla viene **vacía no altera** el origen actual; si trae valor, **lo reutiliza si existe o lo crea automáticamente**.

- **Tipo:** Funcionalidad nueva
- **Horas:** 2
- **Subtotal:** Bs 60,00

### 4. Código de categoría: columna, filtro y edición inline
Incluye:
- Columna **"Código"** en el listado de categorías.
- **Filtro/búsqueda** por código.
- **Edición del código directamente desde el listado** (inline por AJAX, sin recargar la página).
- *Nota: el campo "Código" en el alta/edición de categorías **no se cobra** (se considera parte base del sistema).*

- **Tipo:** Mejora
- **Horas:** 3
- **Subtotal:** Bs 90,00

### 5. Filtros fijos en el POS
Incluye:
- El buscador y las barras de filtro (categoría, código, modelo, origen) **permanecen fijos y visibles** al desplazarse por los productos, agilizando la venta cuando la lista es larga (no hay que volver a subir para filtrar el siguiente producto).

- **Tipo:** Mejora (usabilidad)
- **Horas:** 1
- **Subtotal:** Bs 30,00

### 6. Búsqueda por modelo en el POS
Incluye:
- El buscador de texto del Punto de Venta ahora también encuentra productos por el **nombre del modelo compatible** (ej. "CG150", "BOXER"), además de nombre, código, categoría y origen.

- **Tipo:** Mejora (usabilidad)
- **Horas:** 1
- **Subtotal:** Bs 30,00

---

## Resumen

| N° | Desarrollo                                             | Tipo        | Horas | Subtotal   |
|----|--------------------------------------------------------|-------------|:-----:|-----------:|
| 1  | Módulo "Origen" de productos                           | Nueva       |   6   | Bs 180,00  |
| 2  | Origen en el POS y listados de inventario              | Nueva       |   4   | Bs 120,00  |
| 3  | Origen en la importación de inventario                 | Nueva       |   2   | Bs  60,00  |
| 4  | Código de categoría: columna, filtro y edición inline  | Mejora      |   3   | Bs  90,00  |
| 5  | Filtros fijos en el POS                                 | Mejora      |   1   | Bs  30,00  |
| 6  | Búsqueda por modelo en el POS                           | Mejora      |   1   | Bs  30,00  |
|    | **Total**                                              |             | **17**| **Bs 510,00** |

**Tarifa aplicada:** Bs 30,00 / hora

---

## Notas
- Incluye análisis, desarrollo, pruebas y puesta en funcionamiento.
- Los cambios de estructura de base de datos se entregan con su script SQL para producción.
- No incluye costos de servidor, dominio ni impresión física de material.
- No se cobra el campo "Código" en el alta/edición de categorías (parte base del sistema).
- Validez de la cotización: 15 días.
