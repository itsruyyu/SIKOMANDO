Berikut roadmap backlog backend dan API, dipisahkan antara yang sudah ada, belum ada, dan perlu hardening.

A. Status saat ini
Sudah tersedia atau sudah dikerjakan

Domain

	

Status




Laravel backend

	

Selesai




PostgreSQL dan migration utama

	

Selesai




UUID, timestamps, FK, indexes

	

Selesai




Authentication Sanctum

	

Fondasi tersedia




Login, logout, current user

	

Tersedia




Role dan permission

	

Tersedia




Seeder role, permission, admin, master data

	

Selesai




Master wilayah

	

Tersedia




Grant program

	

Tersedia




Organization

	

Fondasi tersedia




Proposal foundation

	

Tersedia




Proposal budget/RAB

	

Tersedia




Proposal documents

	

Tersedia




Proposal status history

	

Tersedia




Proposal submission

	

Tersedia




Revision foundation

	

Tersedia




Verification API

	

Tersedia dan telah dites




Evaluation API

	

Tersedia dan telah dites




Policy configuration foundation

	

Migration dan model tersedia




Evaluation weight configuration

	

Tersedia




Workflow configuration foundation

	

Tersedia




Notification foundation

	

Route/controller tersedia




Activity foundation

	

Route/controller tersedia




Storage link

	

Selesai




PHP lint aplikasi

	

Lulus




Evaluation focused test

	

Lulus




Full test suite terakhir

	

Lulus sebelum perubahan terakhir

B. Fitur backend dan API yang masih perlu dibuat
1. Authentication dan Account Management
Belum lengkap

Register user jika memang diperlukan.

Refresh token atau token rotation.

Forgot password.

Reset password.

Change password.

Verifikasi email.

Aktivasi dan deaktivasi user.

Update profil user.

Update nomor telepon.

Manajemen sesi/token aktif.

Logout dari seluruh perangkat.

API admin untuk CRUD user.

API admin untuk assign/revoke role.

API admin untuk assign/revoke permission khusus.

Pembatasan login user nonaktif.

Audit login berhasil dan gagal.

Rate limiting endpoint sensitif selain login.

Endpoint yang diperlukan
POST   /api/v1/auth/register
POST   /api/v1/auth/forgot-password
POST   /api/v1/auth/reset-password
POST   /api/v1/auth/change-password
GET    /api/v1/auth/me
PATCH  /api/v1/auth/me
POST   /api/v1/auth/logout
POST   /api/v1/auth/logout-all
2. User, Role, dan Permission Management
Belum lengkap

CRUD user oleh admin.

CRUD role.

CRUD permission.

Assign role ke user.

Assign permission ke role.

Permission override per user.

Aktivasi/nonaktivasi user.

Filter user berdasarkan role, status, dan organisasi.

Audit perubahan role dan permission.

Endpoint yang diperlukan
GET    /api/v1/admin/users
POST   /api/v1/admin/users
GET    /api/v1/admin/users/{user}
PATCH  /api/v1/admin/users/{user}
DELETE /api/v1/admin/users/{user}

GET    /api/v1/admin/roles
POST   /api/v1/admin/roles
GET    /api/v1/admin/roles/{role}
PATCH  /api/v1/admin/roles/{role}

GET    /api/v1/admin/permissions
POST   /api/v1/admin/roles/{role}/permissions
DELETE /api/v1/admin/roles/{role}/permissions/{permission}
POST   /api/v1/admin/users/{user}/roles
DELETE /api/v1/admin/users/{user}/roles/{role}
3. Master Data Management
Sebagian sudah ada, tetapi CRUD admin belum lengkap

Province management.

Regency management.

District management.

Village management.

Grant program management.

Organization type management.

Organization status management.

Document type management.

Requirement management.

Evaluation criteria management.

Master data activation/deactivation.

Import master data.

Export master data.

Validasi parent-child wilayah.

Pencegahan penghapusan master data yang sudah dipakai.

Endpoint yang diperlukan
GET    /api/v1/provinces
GET    /api/v1/regencies
GET    /api/v1/districts
GET    /api/v1/villages

GET    /api/v1/admin/grant-programs
POST   /api/v1/admin/grant-programs
GET    /api/v1/admin/grant-programs/{grantProgram}
PATCH  /api/v1/admin/grant-programs/{grantProgram}

GET    /api/v1/admin/document-types
POST   /api/v1/admin/document-types
PATCH  /api/v1/admin/document-types/{documentType}

GET    /api/v1/admin/requirements
POST   /api/v1/admin/requirements
PATCH  /api/v1/admin/requirements/{requirement}

GET    /api/v1/admin/evaluation-criteria
POST   /api/v1/admin/evaluation-criteria
PATCH  /api/v1/admin/evaluation-criteria/{criteria}
4. Organization Management
Belum lengkap

Create organization.

Update organization.

Detail organization.

Organization verification.

Organization status lifecycle.

Upload legal documents.

Add organization members.

Remove organization members.

Assign organization administrator.

View organization proposals.

View organization documents.

Duplicate organization detection.

Organization profile completeness.

Organization ownership/access control.

Endpoint yang diperlukan
GET    /api/v1/organizations
POST   /api/v1/organizations
GET    /api/v1/organizations/{organization}
PATCH  /api/v1/organizations/{organization}

GET    /api/v1/organizations/{organization}/members
POST   /api/v1/organizations/{organization}/members
DELETE /api/v1/organizations/{organization}/members/{user}

GET    /api/v1/organizations/{organization}/documents
POST   /api/v1/organizations/{organization}/documents
5. Proposal Management
Fondasi sudah ada, tetapi workflow lengkap masih perlu diperkuat

Draft proposal.

Update proposal.

Delete draft proposal.

Detail proposal lengkap.

Proposal ownership.

Proposal organization access.

Upload dokumen proposal.

Replace dokumen.

Delete dokumen sebelum submission.

Validasi kelengkapan proposal.

Submit proposal.

Withdraw proposal jika diperbolehkan.

Reopen proposal oleh admin.

Proposal status transition matrix.

Proposal duplicate checking.

Proposal search dan filter.

Pagination dan sorting.

Proposal export PDF/Excel.

Proposal timeline.

Proposal activity history.

Endpoint yang diperlukan
GET    /api/v1/proposals
POST   /api/v1/proposals
GET    /api/v1/proposals/{proposal}
PATCH  /api/v1/proposals/{proposal}
DELETE /api/v1/proposals/{proposal}

POST   /api/v1/proposals/{proposal}/submit
POST   /api/v1/proposals/{proposal}/withdraw
GET    /api/v1/proposals/{proposal}/timeline
GET    /api/v1/proposals/{proposal}/documents
POST   /api/v1/proposals/{proposal}/documents
DELETE /api/v1/proposals/{proposal}/documents/{document}
6. Proposal Revision
Fondasi sudah tersedia, tetapi perlu workflow penuh

Create revision.

Edit revision.

Submit revision.

Review revision oleh verifikator.

Approve revision.

Reject revision.

Revision request dari verifikator.

Revision deadline/SLA.

Revision history.

Perbandingan proposal sebelum dan sesudah revisi.

Lock revision setelah submission.

Validasi hanya revision aktif yang dapat diedit.

Endpoint yang diperlukan
GET    /api/v1/proposals/{proposal}/revisions
POST   /api/v1/proposals/{proposal}/revisions
GET    /api/v1/proposals/{proposal}/revisions/{revision}
PATCH  /api/v1/proposals/{proposal}/revisions/{revision}
POST   /api/v1/proposals/{proposal}/revisions/{revision}/submit
POST   /api/v1/proposals/{proposal}/revisions/{revision}/approve
POST   /api/v1/proposals/{proposal}/revisions/{revision}/reject
GET    /api/v1/proposals/{proposal}/revisions/compare
7. Verification Management
API dasar sudah selesai, fitur lanjutan masih perlu dibuat

Assignment verifikator.

Reassignment verifikator.

Verification workload queue.

Verification SLA.

Checklist persyaratan.

Verifikasi dokumen.

Verifikasi data organisasi.

Verifikasi RAB.

Request revision.

Approve verification.

Reject verification.

Verification notes.

Verification evidence.

Verification history.

Lock verification setelah complete.

Dashboard antrean verifikasi.

Filter berdasarkan SLA dan status.

Endpoint yang diperlukan
GET    /api/v1/verifications/queue
POST   /api/v1/proposals/{proposal}/verifications/assign
POST   /api/v1/verifications/{verification}/reassign
GET    /api/v1/verifications/{verification}/history
POST   /api/v1/verifications/{verification}/request-revision
POST   /api/v1/verifications/{verification}/approve
POST   /api/v1/verifications/{verification}/reject
8. Evaluation Management
API dasar sudah selesai, tetapi masih perlu pengembangan

Assignment evaluator.

Reassignment evaluator.

Evaluation queue.

Evaluation SLA.

Evaluation rubric management.

Dynamic evaluation criteria.

Weight configuration per program.

Score validation.

Evaluation notes dan evidence.

Evaluation recommendation.

Evaluation approval.

Evaluation locking.

Evaluation history.

Reopen evaluation dengan otorisasi khusus.

Multi-evaluator evaluation.

Evaluation aggregation.

Evaluation comparison.

Ranking kandidat proposal.

Conflict of interest declaration.

Evaluator workload dashboard.

Endpoint yang diperlukan
GET    /api/v1/evaluations/queue
POST   /api/v1/proposals/{proposal}/evaluations/assign
POST   /api/v1/evaluations/{evaluation}/reassign
GET    /api/v1/evaluations/{evaluation}/history
POST   /api/v1/evaluations/{evaluation}/request-reopen
POST   /api/v1/evaluations/{evaluation}/declare-conflict
GET    /api/v1/evaluations/{evaluation}/summary
9. Field Survey / Surveyor
Belum selesai atau belum terverifikasi lengkap

Assignment surveyor.

Survey schedule.

Survey location.

Survey checklist.

Survey questionnaire.

Upload foto atau evidence.

GPS coordinate.

Survey report.

Survey recommendation.

Survey approval.

Survey revision.

Survey history.

Survey SLA.

Survey result integration ke evaluation.

Endpoint yang diperlukan
GET    /api/v1/surveys
POST   /api/v1/proposals/{proposal}/surveys
GET    /api/v1/surveys/{survey}
PATCH  /api/v1/surveys/{survey}
POST   /api/v1/surveys/{survey}/assign
POST   /api/v1/surveys/{survey}/start
POST   /api/v1/surveys/{survey}/complete
POST   /api/v1/surveys/{survey}/evidence
GET    /api/v1/surveys/{survey}/report
10. Ranking dan Recommendation Engine
Belum lengkap

Ranking proposal berdasarkan score.

Formula ranking configurable.

Tie-breaker.

Minimum passing score.

Mandatory criteria.

Budget availability consideration.

Quota per program/wilayah.

Ranking snapshot.

Ranking recalculation.

Ranking approval.

Ranking publication.

Ranking audit history.

Endpoint yang diperlukan
GET    /api/v1/grant-programs/{grantProgram}/ranking
POST   /api/v1/grant-programs/{grantProgram}/ranking/calculate
POST   /api/v1/grant-programs/{grantProgram}/ranking/finalize
GET    /api/v1/proposals/{proposal}/ranking
11. Decision Management
Belum dibuat secara lengkap

Decision draft.

Decision review.

Decision approval.

Decision rejection.

Decision number generation.

Decision template.

Decision letter generation.

Digital document generation.

Signature workflow.

Maker-checker approval.

Decision revision.

Decision cancellation.

Public decision publication.

Decision history.

Endpoint yang diperlukan
GET    /api/v1/decisions
POST   /api/v1/proposals/{proposal}/decisions
GET    /api/v1/decisions/{decision}
PATCH  /api/v1/decisions/{decision}
POST   /api/v1/decisions/{decision}/submit
POST   /api/v1/decisions/{decision}/approve
POST   /api/v1/decisions/{decision}/reject
POST   /api/v1/decisions/{decision}/generate-document
POST   /api/v1/decisions/{decision}/publish
12. Disbursement / Pencairan
Belum lengkap

Disbursement plan.

Disbursement stage.

Verification of bank account.

Disbursement request.

Approval pencairan.

Payment instruction.

Payment status.

Failed payment handling.

Payment evidence.

Disbursement history.

Partial disbursement.

Reconciliation.

Integration dengan sistem keuangan jika diperlukan.

Endpoint yang diperlukan
GET    /api/v1/proposals/{proposal}/disbursements
POST   /api/v1/proposals/{proposal}/disbursements
GET    /api/v1/disbursements/{disbursement}
PATCH  /api/v1/disbursements/{disbursement}
POST   /api/v1/disbursements/{disbursement}/submit
POST   /api/v1/disbursements/{disbursement}/approve
POST   /api/v1/disbursements/{disbursement}/process
POST   /api/v1/disbursements/{disbursement}/reconcile
13. LPJ / Accountability Reporting
Belum lengkap

LPJ submission.

LPJ document upload.

Realization of budget.

Budget variance.

Activity realization.

Output/outcome realization.

LPJ verification.

LPJ revision.

LPJ approval.

LPJ rejection.

Late submission handling.

Reminder deadline.

Final accountability status.

Audit evidence.

Endpoint yang diperlukan
GET    /api/v1/proposals/{proposal}/lpj
POST   /api/v1/proposals/{proposal}/lpj
GET    /api/v1/lpj/{lpj}
PATCH  /api/v1/lpj/{lpj}
POST   /api/v1/lpj/{lpj}/submit
POST   /api/v1/lpj/{lpj}/request-revision
POST   /api/v1/lpj/{lpj}/approve
POST   /api/v1/lpj/{lpj}/reject
POST   /api/v1/lpj/{lpj}/documents
14. Policy & Configuration Management
Migration/model foundation sudah ada, tetapi API workflow belum lengkap

CRUD policy configuration.

Create policy version.

Edit draft policy version.

Submit policy version.

Review policy version.

Approve policy version.

Reject policy version.

Activate policy version.

Schedule effective date.

Expire policy version.

Version comparison.

Rollback.

Maker-checker enforcement.

Audit configuration changes.

Configuration validation.

Preview impact of configuration changes.

Endpoint yang diperlukan
GET    /api/v1/admin/policies
POST   /api/v1/admin/policies
GET    /api/v1/admin/policies/{policy}
POST   /api/v1/admin/policies/{policy}/versions
GET    /api/v1/admin/policies/{policy}/versions
PATCH  /api/v1/admin/policy-versions/{version}
POST   /api/v1/admin/policy-versions/{version}/submit
POST   /api/v1/admin/policy-versions/{version}/approve
POST   /api/v1/admin/policy-versions/{version}/reject
POST   /api/v1/admin/policy-versions/{version}/activate
15. Notification System
Fondasi route/controller sudah tersedia, tetapi engine belum lengkap

Notification creation service.

Notification templates.

Notification by event.

In-app notification.

Email notification.

Notification preference.

Notification retry.

Notification queue.

Notification delivery log.

Reminder SLA.

Reminder deadline.

Broadcast announcement.

Role-based notification.

Endpoint yang diperlukan
GET    /api/v1/notifications
GET    /api/v1/notifications/unread-count
POST   /api/v1/notifications/{notification}/read
POST   /api/v1/notifications/mark-all-as-read
GET    /api/v1/notification-preferences
PATCH  /api/v1/notification-preferences
16. Activity Log dan Audit Trail
Fondasi tersedia, tetapi integrasi menyeluruh belum selesai

Audit harus diterapkan pada:

Login/logout.

User creation/update.

Role dan permission changes.

Organization changes.

Proposal creation/update/submission.

Document upload/delete.

Revision actions.

Verification actions.

Evaluation scoring.

Evaluation completion.

Survey actions.

Decision approval.

Disbursement actions.

LPJ actions.

Policy changes.

Data export.

Public publication.

Data audit minimal:

actor_id
action
entity_type
entity_id
old_values
new_values
ip_address
user_agent
request_id
created_at
17. Public Transparency API

Karena public user tidak perlu login, API publik perlu dipisahkan dari API internal.

Belum lengkap

Public landing page data.

Program hibah aktif.

Announcement.

Timeline program.

Public statistics.

Number of proposals.

Number of approved grants.

Total disbursed amount.

Public decision list.

Public recipient list.

Data anonymization.

Public API rate limit.

Public cache.

Endpoint yang diperlukan
GET /api/v1/public/overview
GET /api/v1/public/grant-programs
GET /api/v1/public/announcements
GET /api/v1/public/statistics
GET /api/v1/public/timeline
GET /api/v1/public/decisions
GET /api/v1/public/recipients
18. Dashboard dan Reporting API
Belum lengkap

Dashboard super admin.

Dashboard admin SIKOMANDO.

Dashboard pemohon.

Dashboard verifikator.

Dashboard evaluator.

Dashboard surveyor.

Proposal statistics.

Verification statistics.

Evaluation statistics.

Approval statistics.

Disbursement statistics.

LPJ statistics.

SLA monitoring.

Workload monitoring.

Geographic distribution.

Budget absorption.

Export report.

Endpoint yang diperlukan
GET /api/v1/dashboard/summary
GET /api/v1/dashboard/proposals
GET /api/v1/dashboard/verifications
GET /api/v1/dashboard/evaluations
GET /api/v1/dashboard/disbursements
GET /api/v1/dashboard/lpj
GET /api/v1/reports/proposals
GET /api/v1/reports/financial
GET /api/v1/reports/performance
19. File Management
Belum lengkap

Secure file download.

File authorization.

File metadata.

File versioning.

MIME validation.

File size validation.

Virus/malware scanning.

File checksum.

File preview.

File expiration.

Private storage access.

Signed download URL.

Document replacement history.

20. Cross-cutting Backend Hardening

Ini wajib dilakukan sebelum backend dianggap siap digunakan.

Security

Form Request validation seluruh endpoint.

Policy seluruh resource.

Consistent authorization.

Prevent IDOR.

Prevent mass assignment.

Rate limiting.

Secure file access.

Sensitive field hiding.

Password policy.

Token expiration.

CORS configuration.

Security headers.

Error response tanpa stack trace production.

Data integrity

Transaction pada workflow penting.

Database constraints.

Unique constraints.

Prevent invalid status transition.

Prevent duplicate active records.

Optimistic locking atau equivalent strategy.

Idempotency untuk action endpoints.

Consistent UUID handling.

API quality

Consistent response format.

Standard error format.

Pagination format.

Filtering.

Sorting.

Search.

API versioning.

OpenAPI/Swagger documentation.

Postman collection.

API examples.

HTTP status consistency.

C. Urutan pengerjaan yang disarankan

Agar tidak berputar-putar, urutan berikut paling masuk akal:

Batch Q — Evaluation Hardening

Authorization final.

Assignment evaluator.

Reassignment.

Evaluation queue.

Evaluation history.

Conflict of interest.

Audit trail evaluation.

Test authorization lengkap.

Batch R — Field Survey

Survey model/service.

Survey assignment.

Survey checklist.

Survey evidence.

Survey completion.

Survey API.

Test survey workflow.

Batch S — Ranking Engine

Ranking rule service.

Score aggregation.

Tie-breaker.

Ranking snapshot.

Ranking API.

Test ranking.

Batch T — Decision Management

Decision service.

Decision workflow.

Maker-checker.

Numbering.

Template.

Decision API.

Test decision approval.

Batch U — Disbursement

Disbursement workflow.

Payment stages.

Approval.

Reconciliation.

Disbursement API.

Test pencairan.

Batch V — LPJ

LPJ submission.

Realization.

Verification.

Approval.

LPJ API.

Test LPJ.

Batch W — Policy API

Policy CRUD.

Versioning.

Approval.

Activation.

Rollback.

Audit.

Test maker-checker.

Batch X — Notification dan SLA

Event notification.

Queue.

Reminder.

SLA calculation.

Notification preferences.

Notification API.

Batch Y — Public Transparency

Public statistics.

Public programs.

Announcements.

Public decisions.

Public API cache dan rate limit.

Batch Z — Final QA dan Production Readiness

Full integration test.

Authorization matrix test.

API documentation.

Performance test.

Security test.

Queue test.

Storage test.

Backup/restore test.

Deployment configuration.

Production environment validation.