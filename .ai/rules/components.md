---
paths:
  - resources/views/components/panel.blade.php
---

# Components

## Panel = Specimen Ledger anatomy (status slot + figures band)
The panel component is the Specimen Ledger chassis: title on the left, `status` named slot (badges) + `actions` string on the right, optional `figures` array prop (label/value/metric -> dl.panel-figures ruled band with data-metric). Figures bands are the only count anatomy in admin — do not reintroduce .admin-counts/.admin-record-counts/.admin-evidence-totals. Stacked panels go in a .admin-ledger wrapper (shares single hairlines).

## Panel actions bind with :actions, not actions="{{ }}"
Binding HTML through actions="{{ $html }}" escapes it (Blade escapes attribute values) and the component's {!! $actions !!} then prints the anchor markup as visible text. Bind the string prop with :actions="$html" so real links render. Header action chips on the admin console use the .panel-action class (app.css @layer components); figures bands remain the only count anatomy.
