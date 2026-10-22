---
paths:
  - app/Support/SchemaAvailability.php
---

# Support

## Optional schema must degrade, never crash, while its DDL is pending
The application schema is applied on its own approved step, so a deployed database can lag the code. Probe optional columns/tables through SchemaAvailability (cached per process, logs the pending migration). Missing attribution column -> omit the attribute; missing evidence columns -> write the verdict without evidence; missing hidden-test table -> no hidden test can exist, so the legacy pattern-only path is honest. Never let an optional column turn a write into a 500, and never silently downgrade a graded verdict.
