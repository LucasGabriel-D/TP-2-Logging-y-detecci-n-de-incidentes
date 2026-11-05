Pruebas realizadas

| Prueba | Resultado esperado | Resultado obtenido | Evidencia |
|---|---|---|---|
| Login válido | INFO y registro success | [OK] | capturas/01-login-ok.png |
| Dos contraseñas incorrectas | Dos eventos WARNING | [OK] | capturas/02-warning.png |
| Tres fallos en cinco minutos | ALERT por posible fuerza bruta | [OK] | capturas/03-alert.png |
| Cinco fallos en cinco minutos | Bloqueo 10 min y CRITICAL | [OK] | capturas/04-critical.png |
| Acceso con IP bloqueada | HTTP 429 y registro blocked | [OK] | capturas/05-429.png |
| Acceso tras vencer el bloqueo | Autenticación habilitada nuevamente | [OK] | capturas/06-desbloqueo.png |
| Panel con usuario sin permiso | HTTP 403 | [OK] | capturas/07-403.png |

Entre escenarios se reinició el estado con `php artisan cache:clear` y `TRUNCATE login_attempts;`.

Extracto del log:
<img width="1013" height="578" alt="image" src="https://github.com/user-attachments/assets/8061da71-ac52-4681-8493-bd9f49dc50e8" />

Consultas SQL ejecutadas en phpMyAdmin: ver `capturas/08-consultas.png`.

Análisis y decisiones de seguridad

¿Qué IP concentra los fallos?
La IP `[127.0.0.1]`, con `3` intentos fallidos según la consulta de fallos acumulados por IP.
Como las pruebas se hicieron en local, todos los intentos provienen de la misma dirección
(loopback). En un entorno real se vería la IP pública de cada cliente, siempre que se configuren
los proxies confiables; si no, todas las peticiones parecerían venir del proxy.

¿Puede afirmarse que existe un ataque?
No con certeza. Cinco fallos en cinco minutos son un indicio, no una prueba: un usuario legítimo
que olvidó su contraseña genera el mismo patrón. Para hablar de un ataque harían falta otros
indicadores, como muchos intentos contra cuentas distintas desde la misma IP (password spraying),
la misma cuenta atacada desde muchas IP, una frecuencia imposible para un humano, user agents de
herramientas automatizadas, o un login exitoso inmediatamente después de muchos fallos (posible
compromiso de la cuenta).

¿Qué efectos tendría bloquear una IP compartida?
Se bloquearía a todos los usuarios detrás de esa IP: una universidad, una empresa, un cibercafé o
una red móvil con NAT, donde cientos de personas salen por la misma dirección. Un solo usuario (o
un atacante) podría dejar sin acceso a todos los demás, lo que convierte al propio bloqueo en una
forma de denegación de servicio. Por eso conviene combinar límites por IP y por cuenta, y usar
bloqueos cortos y progresivos.

¿Qué evidencias adicionales se necesitan?
Las cuentas afectadas por cada IP, el user agent, la distribución horaria de los intentos, la
geolocalización y reputación de la IP, si hubo un éxito posterior a los fallos, y la correlación
con otros logs (servidor web, firewall). También es necesario que los relojes estén sincronizados
(NTP) y que los logs estén protegidos contra modificación, para que sirvan como evidencia.

¿Qué respuestas conviene automatizar?**
Conviene automatizar las respuestas rápidas, temporales y reversibles: el registro de eventos, las
alertas, el bloqueo temporal de 10 minutos y las notificaciones al equipo de seguridad. En cambio,
requieren revisión humana las decisiones de mayor impacto: bloqueos permanentes, bloqueo de rangos
de IP, desactivación de cuentas, reseteo forzado de contraseñas o una denuncia.

Mejoras implementadas (actividad integradora)

1. **Detección por cuenta:** además de contar fallos por IP, se cuentan los fallos sobre un mismo
   email en los últimos 15 minutos. Con 5 o más se registra un ALERT. Esto detecta ataques que
   rotan IP para evadir el límite por dirección.
2. **Exportación de evidencias:** desde el panel se descarga un CSV con todos los intentos de las
   últimas 24 horas (`/security-dashboard/export`), protegido con el mismo Gate que el panel.
