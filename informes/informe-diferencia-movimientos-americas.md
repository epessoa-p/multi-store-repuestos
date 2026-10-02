**Para:** [ Nombre del cliente ]
**De:** [ Eric Pessoa Montaño ]
**Fecha:** 02/10/2026
**Asunto:** Diferencia en los montos de la sucursal AMERICAS (pantalla Movimientos)

---

## 1. ¿Qué se observó?

En la pantalla **Movimientos**, viendo "Todo el historial", los montos de la sucursal **AMERICAS** no coincidían entre el recuadro **Histórico** (arriba a la derecha) y el resumen de **Transacciones**:

| AMERICAS | Histórico | Transacciones | Diferencia |
|---|---:|---:|---:|
| Ingresos | Bs 61,672.10 | Bs 60,596.10 | Bs 1,076.00 |
| Egresos  | Bs 61,027.10 | Bs 47,472.80 | Bs 13,554.30 |
| Balance  | Bs 645.00    | Bs 13,123.30 | — |

En la sucursal PLAN 4MIL y en "Todas" los montos sí coincidían.

## 2. ¿Por qué pasó?

El **15/09/2026** se **eliminó la caja "CAJA GERENCIA"** de la sucursal AMERICAS. Esa caja **ya tenía movimientos registrados** (ventas, gastos, etc.) entre el 23/06 y el 10/09/2026, por un total de:

- **Ingresos:** Bs 1,076.00
- **Egresos:** Bs 13,554.30

Son exactamente los montos de la diferencia.

Al eliminar la caja, sus movimientos **no se perdieron**: siguieron guardados y el recuadro Histórico los seguía sumando. En cambio, al filtrar por la sucursal AMERICAS, el sistema dejaba fuera los movimientos de esa caja eliminada. Por eso la misma sucursal mostraba dos totales distintos.

**No se trató de ventas anuladas ni de dinero faltante.** Todos los registros están completos.

## 3. ¿Qué se corrigió?

- Ahora el sistema **incluye los movimientos de las cajas eliminadas** en todos los reportes de Movimientos, porque ese dinero sí se movió y pertenece a su sucursal.
- En "Cierres de caja", las sesiones de una caja eliminada se muestran con la etiqueta **"Caja eliminada"**, para que se identifiquen.
- **No se modificó ni se borró ningún dato.**

**Resultado actual** (todo el historial):

| Sucursal | Ingresos | Egresos | Balance |
|---|---:|---:|---:|
| AMERICAS | Bs 61,672.10 | Bs 61,027.10 | Bs 645.00 |
| PLAN 4MIL | Bs 3,990.50 | Bs 3,950.50 | Bs 40.00 |
| **Total** | **Bs 65,662.60** | **Bs 64,977.60** | **Bs 685.00** |

El Histórico y Transacciones ahora coinciden en todas las sucursales.

## 4. Prevención: ya no se pueden eliminar cajas con movimientos

Para que esto no vuelva a ocurrir, se agregó una **protección en el sistema**:

- Una caja que **ya tiene movimientos o cierres registrados no se puede eliminar**. En el listado de cajas, el botón de eliminar aparece como un **candado gris** y, si se intenta igualmente, el sistema muestra un aviso y no la elimina.
- Si una caja ya no se va a usar, lo correcto es **desactivarla** (en **Editar**, desmarcar la opción **"Activa"**): deja de estar disponible para trabajar, pero su historial queda intacto y ordenado en los reportes.
- Las cajas creadas por error que **nunca se usaron** se pueden seguir eliminando normalmente.
