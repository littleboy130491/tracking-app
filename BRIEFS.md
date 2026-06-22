Project brief:
Web application tracking untuk dokumen Bill of Lading (BL) for freight forwarding company.

- 2 dashboards: admin (filament) and customers (breeze)

Specs:

1. Customer Dashboard dengan Passwordless Login (OTP)

Dashboard khusus untuk customer agar dapat melihat dan memantau status dokumen BL secara mandiri.

Setiap customer akan memiliki akun masing-masing, dengan ketentuan dan kapabilitas:

    1 akun menggunakan 1 alamat email

    Akun customer dibuat secara manual oleh admin

    Customer hanya dapat mengakses dashboard setelah login

    Sistem login menggunakan OTP / passwordless login yang akan dikirimkan ke email terdaftar, sehingga customer tidak perlu membuat atau mengingat password

    Data tracking BL hanya dapat diakses oleh customer yang sudah login

    Setiap customer hanya dapat melihat data BL yang berkaitan dengan akun masing-masing untuk menjaga kerahasiaan data dan mencegah akses publik terhadap informasi dokumen

2. Admin Dashboard

Admin dapat menambahkan dan mengelola data dokumen BL melalui admin panel.

Data yang dapat dikelola mencakup:

    Nomor BL

    Nama customer / perusahaan

    Deskripsi shipment

    Tanggal input

    Status dokumen

    Phase / tahapan proses

    Catatan update

    URL tracking GPS eksternal

    Riwayat update status

3. Update Status Berdasarkan Flow / Phase

Sistem memiliki alur update status berdasarkan phase atau tahapan proses tertentu.

Setiap admin dapat diberikan role sesuai phase masing-masing. Admin hanya dapat melakukan update status pada phase yang menjadi tanggung jawabnya.

Contoh alur:

    Admin 1 mengupdate phase awal

    Setelah phase awal selesai, Admin 2 baru dapat mengupdate phase berikutnya

    Admin berikutnya hanya dapat melakukan update apabila proses sebelumnya sudah terupdate

4. Field URL Tracking GPS Eksternal

Sistem menyediakan field khusus untuk memasukkan URL tracking GPS dari aplikasi eksternal.

Customer dapat mengklik link tracking tersebut melalui dashboard, sehingga sistem dapat mengarahkan customer ke platform tracking yang sudah digunakan.

5. Retensi Data Minimal 3 Tahun

Data tracking BL disimpan dengan masa retensi minimal 3 tahun.

Dengan kebutuhan rata-rata pergerakan data sekitar 30–50 data per hari, sistem akan dirancang agar tetap dapat menampung dan mengelola data historis secara rapi, aman, dan mudah dicari kembali apabila dibutuhkan.

Phase 1: wireframe or mockup for clients

- 2 dashboards, admin and customers
- 3 accounts, 1 account for admin, 2 accounts for customers
- dummy data and fields
- testing data for customers, each customer can only check their own documents
- admin can create customer, create data, update data, BL number should be unique
- admin login using email and password
- customer login using email, and it will get OTP to their email address, for now OTP should be displayed directly in the form, because we don't setting SMTP for now
