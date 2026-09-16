---
name: commit-split
description: Divide los cambios pendientes en staging y working tree en commits atómicos e independientes siguiendo Conventional Commits en español.
---

Actúa como un Senior Software Engineer enfocado en buenas prácticas de Git y arquitectura limpia.

Analiza todos los cambios pendientes en este espacio de trabajo (staging y working tree) y divídelos en commits atómicos independientes siguiendo la especificación Conventional Commits v1.0.0.

Reglas obligatorias:
1. **Idioma**: Todo el resultado, explicaciones, justificaciones y los mensajes de commit (`<descripción>`) **DEBEN estar redactados completamente en español**.
2. Divide por responsabilidad única: no mezcles lógica de negocio, pruebas, dependencias, refactorizaciones ni documentación en un mismo commit.
3. Si un solo archivo contiene cambios para dos propósitos distintos, indícalo para usar `git add -p`.
4. Estructura de cada commit:
   - Tipo permitido: feat, fix, docs, style, refactor, perf, test, build, ci, chore.
   - Formato: `<tipo>(<alcance opcional>): <descripción concisa en español, en tiempo presente/imperativo y en minúsculas>`
   - Comando exacto de staging (`git add <rutas>`).
   - Comando exacto del commit (`git commit -m "..."`).
   - Justificación breve del porqué van juntos estos cambios (en español).
5. Entrega la lista ordenada en la secuencia lógica exacta en que deben ejecutarse en la terminal.

