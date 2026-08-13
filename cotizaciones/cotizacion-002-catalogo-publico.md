**Para:** [ Nombre del cliente ]
**De:** [ Eric Pessoa Montaño ]
**Fecha:** [ 12/08/2026 ]
**Cotización N°:** [ 002 ]

---

## Objetivo

Publicar un **catálogo de productos de solo consulta** al que cualquier cliente pueda acceder por **enlace o código QR** (sin iniciar sesión), para:
- Reducir la carga del vendedor cuando llegan muchos clientes solo a preguntar producto y precio.
- Mostrar el **precio oficial del sistema**, de modo que el precio quede a la vista del cliente y se evite el cobro por encima de lo registrado.
- Permitir consultar la **disponibilidad por sucursal** y **descargar el catálogo en PDF**.

---

## Detalle de los desarrollos

### 1. Catálogo público de productos (vista del cliente)
Página pública de solo lectura, **una por sucursal**, accesible por enlace o QR, que muestra los productos con su precio oficial y su disponibilidad.

Incluye:
- Acceso **sin necesidad de iniciar sesión** (solo consulta), por enlace o código QR.
- Listado de **todos los productos activos** con **foto**, categoría/marca y **precio oficial**.
- Estado de **disponibilidad (Disponible / Agotado)** en la sucursal del catálogo, con opción de ver la disponibilidad en las **demás sucursales**.
- **Buscador instantáneo** y **filtro por categoría**.
- **Descarga del catálogo en PDF**.
- Diseño **responsive** (celular, tablet y PC) y optimización de rendimiento para manejar cientos de productos sin demoras.

- **Tipo:** Funcionalidad nueva
- **Horas:** 9
- **Subtotal:** Bs 270,00

### 2. Panel de administración del catálogo
Sección administrativa para gestionar y compartir el catálogo de cada sucursal.

Incluye:
- Nueva sección **«Catálogo»** dentro del menú de Inventario.
- **Enlace público** y **código QR imprimible** por sucursal (con botón de imprimir y de copiar enlace).
- Botones para **ver** el catálogo y **descargar el PDF**.
- **Activar / desactivar** la publicación de cada sucursal de forma independiente.
- **Regenerar el enlace** (invalida el anterior, por seguridad).
- Seguridad: acceso mediante **token no adivinable**, **límite de solicitudes** (anti-abuso) y validación por empresa/sucursal.

- **Tipo:** Funcionalidad nueva
- **Horas:** 5
- **Subtotal:** Bs 150,00

---

## Resumen

| N° | Desarrollo                                   | Horas | Subtotal   |
|----|----------------------------------------------|:-----:|-----------:|
| 1  | Catálogo público de productos (vista cliente)|   9   | Bs 270,00  |
| 2  | Panel de administración del catálogo         |   5   | Bs 150,00  |
|    | **Total**                                    | **14**| **Bs 420,00** |

**Tarifa aplicada:** Bs 30,00 / hora

---

## Notas
- Incluye análisis, desarrollo, pruebas y puesta en funcionamiento del módulo.
- El cambio de estructura de base de datos se entrega con su script SQL para producción.
- No incluye costos de servidor, dominio ni impresión física de los códigos QR.
- Validez de la cotización: 15 días.
