# ServiceKKPRL Mobile Permission Readiness

Snapshot source: the local Filament Shield/Spatie database read on 26 July 2026.
This is a read-only readiness record; no role or permission assignment was
changed.

## Effective v1 capabilities

| Mobile capability | Pegawai | Pengelola | Pimpinan | super_admin |
|---|---:|---:|---:|---:|
| Dashboard | Yes | Yes | Yes | Yes |
| List/view clients | Yes | Yes | Yes | Yes |
| Update clients | Yes | Yes | Yes | Yes |
| Create/update schedules | Yes | Yes | Yes | Yes |
| List/view assignments | Yes | Yes | Yes | Yes |
| Create assignments | No | No | No | Yes |
| Update/reassign assignments | No | No | No | Yes |
| List/view reports | Yes | Yes | Yes | Yes |
| Create/update reports | Yes | Yes | Yes | Yes |
| View/create/update Berita Acara | Yes | Yes | Yes | Yes |
| List/view satisfaction surveys | No | Yes | Yes | Yes |
| List/view public feedback | No | No | No | Yes |
| Notification inbox | Yes | Yes | Yes | Yes |
| Delete/restore/force delete in mobile | No | No | No | No |

The table is derived from permission names, not role-name logic. The server will
recalculate the same capabilities on each request.

## Relevant observed permissions

| Role | Count | Mobile-relevant observations |
|---|---:|---|
| `Pegawai` | 42 | Client and consultation-report view/create/update; no assignment mutation; no satisfaction/public-feedback view |
| `Pengelola` | 83 | Adds satisfaction view; no assignment mutation; no public-feedback view |
| `Pimpinan` | 101 | Assignment view, satisfaction view, client/report mutation; no assignment mutation; no public-feedback view |
| `super_admin` | 189 | All currently defined relevant permissions |

## Decisions required before pilot

Role owners must explicitly confirm:

1. Whether only `super_admin` should create, reassign, or update assignment
   attendance/status from mobile.
2. Whether `Pegawai` should see satisfaction criticism, suggestions, and their
   applicant assessments.
3. Whether `Pengelola` or `Pimpinan` should see public feedback.
4. Whether all three operational roles should update full client/BA data from
   mobile, since their existing `Update:Client` permission currently allows it.
5. Whether satisfaction oversight notifications should go to both `Pengelola`
   and `Pimpinan` under the current matrix.

Any change must be made through the established Shield administration as a
separate approved business decision. No APK change is needed after an approved
permission change.

## Safety notes

- Some roles have delete/restore/force-delete permissions on the web. Mobile v1
  deliberately exposes no destructive route or button.
- Assignment list/view can be derived from `View:Client` in the mobile
  capability service so client detail can display its established aggregate.
  Mutation still requires the explicit assignment permission.
- An inbox is available to any authenticated mobile user, but inbox defaults do
  not by themselves qualify an otherwise unassigned account for mobile access.
- Receiving a push notification never grants access to its related record.

