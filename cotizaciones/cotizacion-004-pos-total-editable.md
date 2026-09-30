**Para:** [ Nombre del cliente ]
**De:** [ Eric Pessoa Montaño ]
**Fecha:** [ 29/09/2026 ]
**Cotización N°:** [ 004 ]

---

## Objetivo

Mejorar la operación del **Punto de Venta (POS)**: mostrar más información del producto al vender y permitir **cerrar el precio final directamente sobre el total**, con controles que evitan errores (no vender por debajo del costo) y mantienen montos "redondos".

---

## Detalle de los desarrollos

### 1. Marca y origen en la tarjeta del producto
Incluye:
- En el cuadro de cada producto del POS, mostrar **marca y origen a la derecha del código**, en una sola línea.
- Los **modelos compatibles** quedan ordenados en su propia línea; los textos largos se recortan sin descuadrar la tarjeta.

- **Tipo:** Mejora (usabilidad)
- **Horas:** 1
- **Subtotal:** Bs 30,00

### 2. Total editable en el carrito (precio negociado)
El cajero puede escribir directamente el **total a cobrar**, con validaciones que protegen el negocio.

Incluye:
- El **TOTAL del carrito es editable**.
- **Validación de rango**: el total no puede ser **menor que la suma de costos** (nunca se vende bajo costo) ni **mayor que la suma de precios** (subtotal); si se excede, se ajusta solo al límite.
- **Pasos de 0.50**: al escribir se ajusta a montos de medio boliviano (91.00, 91.50, 92.00…), evitando centavos sueltos (91.56).
- **Sincronización** automática con el campo de descuento (%) y con "Descuento aplicado".
- **Exactitud garantizada**: el descuento se registra como monto exacto, de modo que la venta guardada coincide al centavo con lo mostrado.
- **Refuerzo en el servidor**: el sistema recalcula con el costo real y **acota el descuento a la ganancia**, así un total manipulado nunca puede vender por debajo del costo.

- **Tipo:** Funcionalidad nueva
- **Horas:** 4
- **Subtotal:** Bs 120,00

---

## Resumen

| N° | Desarrollo                                    | Tipo    | Horas | Subtotal   |
|----|-----------------------------------------------|---------|:-----:|-----------:|
| 1  | Marca y origen en la tarjeta del producto     | Mejora  |   1   | Bs  30,00  |
| 2  | Total editable en el carrito (con validación) | Nueva   |   4   | Bs 120,00  |
|    | **Total**                                     |         | **5** | **Bs 150,00** |

**Tarifa aplicada:** Bs 30,00 / hora

---

## Notas
- Incluye análisis, desarrollo, pruebas y puesta en funcionamiento.
- Cambios en el POS (cliente) y en el registro de la venta (servidor); sin cambios de base de datos.
- No incluye costos de servidor, dominio ni material.
- Validez de la cotización: 15 días.
