<x-mail::message>
# Kode Verifikasi Login

Gunakan kode berikut untuk masuk ke akun **BL Tracking** Anda:

<x-mail::panel>
**{{ $otp }}**
</x-mail::panel>

Kode ini berlaku selama **{{ $expiresMinutes }} menit**. Jangan bagikan kode ini kepada siapa pun, termasuk pihak yang mengaku sebagai petugas kami.

Jika Anda tidak meminta kode ini, abaikan email ini. Akun Anda tetap aman.

Hormat kami,<br>
Tim BL Tracking
</x-mail::message>
