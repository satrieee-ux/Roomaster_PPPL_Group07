# Struktur folder views

Nama file tidak berubah (mengikuti LIST KONVENSI PENAMAAN). Yang berubah hanya foldernya.

- **Pages**: dipanggil dengan `view('pages.<folder>.<nama>')`, contoh `view('pages.booking.booking_berhasil')`.
- **Components**: tag tetap `<x-nama_komponen>` tanpa nama folder. Semua subfolder di `resources/views/components/` didaftarkan otomatis di `AppServiceProvider`.
- **Syarat**: nama file komponen harus unik di semua subfolder. Folder komponen baru langsung dikenali, tidak perlu mendaftar apa pun.
- `layouts/` tetap datar.

## resources/views/pages/

| Folder | Isi |
|---|---|
| `auth/` | `splash`, `login`, `signup`, `pilih_akun_its`, `email_its`, `password_its` |
| `rooms/` | `menu_utama`, `rooms`, `rooms_skpb`, `rooms_departemen`, `rooms_fav`, `room_detail`, `room_reviews` |
| `search/` | `melakukan_search`, `melihat_hasil_search` |
| `booking/` | `booking_berhasil`, `booking_berkasterunggah`, `booking_isiformulir_peminjaman`, `booking_melampirkan_dokumenpendukung`, `booking_pilihtanggal_peminjaman`, `booking_ringkasanpeminjaman`, `booking_summary`, `daftar_bookings` |
| `checkin/` | `checkin`, `berhasilcheckin`, `checkout`, `berhasilcheckout` |
| `inbox/` | `percakapan`, `pesan`, `notifikasi` |
| `laporan/` | `Qreporting`, `laporkerusakan`, `laporkerusakan_submitted`, `ringkasanlaporan`, `riwayatlaporan` |

## resources/views/components/

| Folder | Isi |
|---|---|
| `ui/` | `button`, `icon`, `avatar`, `checkbox`, `dropdown`, `form_field`, `form_error`, `popup`, `tabs`, `status_pill`, `status_banner`, `activity_timeline` |
| `nav/` | `header`, `header_compact`, `header_topappbar`, `component_navbar` |
| `auth/` | `auth_page`, `myits_card`, `social_login` |
| `rooms/` | `card_room`, `room_header`, `room_section`, `rooms_page`, `card_review`, `popup_buatreview` |
| `search/` | `header_search`, `popup_filter`, `popup_datepicker` |
| `booking/` | `booking_form`, `booking_stepper`, `doc_upload`, `card_file`, `card_booking`, `dropdown_departemen`, `component_bookbar`, `component_bookdetailbar`, `component_bookdocbar`, `popup_booking_draft`, `popup_booking_pilihtanggal_peminjaman`, `popup_booking_pilihwaktu_peminjaman`, `popup_prebook`, `popup_cancelbooking` |
| `checkin/` | `popup_checkin`, `popup_checkout`, `media_upload` |
| `inbox/` | `card_conversation`, `card_notification`, `chat_bubble`, `component_chatbar`, `header_chat` |
| `laporan/` | `card_report`, `component_reportbar`, `popup_reportconfirm` |
