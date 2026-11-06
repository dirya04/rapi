import fs from 'fs';
import path from 'path';
import {
    Document,
    Packer,
    Paragraph,
    TextRun,
    HeadingLevel,
    Table,
    TableRow,
    TableCell,
    WidthType,
    BorderStyle,
    AlignmentType,
    UnderlineType,
    ImageRun,
    Header,
    Footer,
    PageNumber,
    NumberFormat,
    ShadingType
} from 'docx';

const PRIMARY_COLOR = '123C33'; // Deep Forest Green
const ACCENT_COLOR = '1D7A63';  // Emerald
const GOLD_COLOR = 'C99500';    // Darker Gold for high readability
const MUTED_COLOR = '5D6F68';
const BORDER_COLOR = 'DCE5E0';
const BG_HEADER = 'EDF5F1';
const BG_ZEBRA = 'F8FAF9';

function createHeaderPara(text) {
    return new Paragraph({
        text,
        alignment: AlignmentType.RIGHT,
        children: [
            new TextRun({
                text,
                size: 18,
                font: 'Public Sans',
                color: MUTED_COLOR,
                italics: true
            })
        ]
    });
}

function createFooterPara() {
    return new Paragraph({
        alignment: AlignmentType.CENTER,
        children: [
            new TextRun({
                text: 'Halaman ',
                size: 18,
                font: 'Public Sans',
                color: MUTED_COLOR
            }),
            new TextRun({
                children: [PageNumber.CURRENT],
                size: 18,
                font: 'Public Sans',
                color: MUTED_COLOR,
                bold: true
            }),
            new TextRun({
                text: ' | RAPI-Sekolah v1.0 Disetujui',
                size: 18,
                font: 'Public Sans',
                color: MUTED_COLOR
            })
        ]
    });
}

function heading1(title) {
    return new Paragraph({
        text: title,
        heading: HeadingLevel.HEADING_1,
        spacing: { before: 360, after: 180 },
        border: {
            bottom: {
                color: PRIMARY_COLOR,
                space: 6,
                value: 'single',
                size: 12
            }
        }
    });
}

function heading2(title) {
    return new Paragraph({
        text: title,
        heading: HeadingLevel.HEADING_2,
        spacing: { before: 260, after: 120 }
    });
}

function heading3(title) {
    return new Paragraph({
        text: title,
        heading: HeadingLevel.HEADING_3,
        spacing: { before: 180, after: 80 }
    });
}

function bodyPara(text, options = {}) {
    return new Paragraph({
        spacing: { before: 60, after: 120, line: 280 },
        alignment: options.alignment || AlignmentType.LEFT,
        children: [
            new TextRun({
                text,
                size: 22, // 11pt
                font: 'Public Sans',
                color: options.color || '1A231F',
                bold: options.bold || false,
                italics: options.italics || false
            })
        ]
    });
}

function bulletItem(boldText, normalText) {
    return new Paragraph({
        bullet: { level: 0 },
        spacing: { before: 40, after: 60, line: 280 },
        children: [
            new TextRun({
                text: boldText ? boldText + ' ' : '',
                bold: true,
                size: 22,
                font: 'Public Sans',
                color: '14231E'
            }),
            new TextRun({
                text: normalText,
                size: 22,
                font: 'Public Sans',
                color: '2D3748'
            })
        ]
    });
}

function noteBox(title, message) {
    return new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        rows: [
            new TableRow({
                children: [
                    new TableCell({
                        shading: { type: ShadingType.CLEAR, fill: 'EAF4F0' },
                        borders: {
                            left: { style: BorderStyle.SINGLE, size: 24, color: PRIMARY_COLOR },
                            top: { style: BorderStyle.NONE },
                            right: { style: BorderStyle.NONE },
                            bottom: { style: BorderStyle.NONE }
                        },
                        margins: { top: 160, bottom: 160, left: 240, right: 240 },
                        children: [
                            new Paragraph({
                                children: [
                                    new TextRun({
                                        text: `[${title}] `,
                                        bold: true,
                                        size: 20,
                                        font: 'Public Sans',
                                        color: PRIMARY_COLOR
                                    }),
                                    new TextRun({
                                        text: message,
                                        size: 20,
                                        font: 'Public Sans',
                                        color: '1E302A'
                                    })
                                ]
                            })
                        ]
                    })
                ]
            })
        ]
    });
}

function createStyledTable(headers, rowsData, widths = null) {
    const tableRows = [];

    // Header row
    const headerCells = headers.map((h, i) => {
        return new TableCell({
            width: widths ? { size: widths[i], type: WidthType.PERCENTAGE } : undefined,
            shading: { type: ShadingType.CLEAR, fill: PRIMARY_COLOR },
            margins: { top: 140, bottom: 140, left: 160, right: 160 },
            children: [
                new Paragraph({
                    alignment: AlignmentType.LEFT,
                    children: [
                        new TextRun({
                            text: h,
                            bold: true,
                            size: 20,
                            font: 'Public Sans',
                            color: 'FFFFFF'
                        })
                    ]
                })
            ]
        });
    });
    tableRows.push(new TableRow({ children: headerCells, tableHeader: true }));

    // Data rows
    rowsData.forEach((row, rIndex) => {
        const isZebra = rIndex % 2 === 1;
        const dataCells = row.map((cellText, cIndex) => {
            return new TableCell({
                width: widths ? { size: widths[cIndex], type: WidthType.PERCENTAGE } : undefined,
                shading: { type: ShadingType.CLEAR, fill: isZebra ? BG_ZEBRA : 'FFFFFF' },
                borders: {
                    top: { style: BorderStyle.SINGLE, size: 4, color: BORDER_COLOR },
                    bottom: { style: BorderStyle.SINGLE, size: 4, color: BORDER_COLOR },
                    left: { style: BorderStyle.SINGLE, size: 4, color: BORDER_COLOR },
                    right: { style: BorderStyle.SINGLE, size: 4, color: BORDER_COLOR }
                },
                margins: { top: 120, bottom: 120, left: 140, right: 140 },
                children: [
                    new Paragraph({
                        alignment: AlignmentType.LEFT,
                        spacing: { before: 20, after: 20, line: 260 },
                        children: [
                            new TextRun({
                                text: String(cellText),
                                size: 19,
                                font: 'Public Sans',
                                color: '1A231F'
                            })
                        ]
                    })
                ]
            });
        });
        tableRows.push(new TableRow({ children: dataCells }));
    });

    return new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        rows: tableRows
    });
}

function createImagePara(imagePath, captionText) {
    if (!fs.existsSync(imagePath)) {
        return bodyPara(`[Gambar tidak ditemukan di: ${imagePath}]`);
    }

    const imageBytes = fs.readFileSync(imagePath);
    return [
        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { before: 200, after: 100 },
            children: [
                new ImageRun({
                    data: imageBytes,
                    transformation: {
                        width: 580,
                        height: 326
                    }
                })
            ]
        }),
        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { before: 40, after: 240 },
            children: [
                new TextRun({
                    text: captionText,
                    italics: true,
                    size: 19,
                    font: 'Public Sans',
                    color: MUTED_COLOR
                })
            ]
        })
    ];
}

async function generate() {
    console.log('Menyiapkan dokumen SRS Word...');

    const docChildren = [];

    // ==========================================
    // COVER PAGE
    // ==========================================
    docChildren.push(
        new Paragraph({ spacing: { before: 800 } }),
        new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [
                new TextRun({
                    text: 'SPESIFIKASI KEBUTUHAN PERANGKAT LUNAK',
                    size: 32,
                    font: 'Bricolage Grotesque',
                    bold: true,
                    color: PRIMARY_COLOR
                })
            ]
        }),
        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { before: 80, after: 300 },
            children: [
                new TextRun({
                    text: 'SOFTWARE REQUIREMENTS SPECIFICATION (SRS)',
                    size: 22,
                    font: 'Public Sans',
                    color: MUTED_COLOR,
                    bold: true
                })
            ]
        }),
        new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [
                new TextRun({
                    text: 'Untuk:',
                    size: 22,
                    font: 'Public Sans',
                    color: MUTED_COLOR
                })
            ]
        }),
        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { before: 100, after: 100 },
            children: [
                new TextRun({
                    text: 'RAPI-Sekolah',
                    size: 52,
                    font: 'Bricolage Grotesque',
                    bold: true,
                    color: PRIMARY_COLOR
                })
            ]
        }),
        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 500 },
            children: [
                new TextRun({
                    text: 'Sistem Manajemen Agenda Peminjaman Ruang & Penanganan Pengaduan Fasilitas Sekolah',
                    size: 24,
                    font: 'Public Sans',
                    italics: true,
                    color: '2C433B'
                })
            ]
        }),
        noteBox('INFORMASI DOKUMEN RESMI UKK', 'Dokumen ini disusun untuk memenuhi instrumen Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak berbasis standar IEEE Std 830-1998.'),
        new Paragraph({ spacing: { before: 800 } }),
        new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [
                new TextRun({ text: 'Versi Dokumen: ', size: 22, font: 'Public Sans' }),
                new TextRun({ text: '1.0 Disetujui', size: 22, font: 'Public Sans', bold: true, color: PRIMARY_COLOR })
            ]
        }),
        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { before: 80 },
            children: [
                new TextRun({ text: 'Disiapkan oleh: ', size: 22, font: 'Public Sans' }),
                new TextRun({ text: 'Dirya & Tim Pengembang Perangkat Lunak (UKK RPL)', size: 22, font: 'Public Sans', bold: true })
            ]
        }),
        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { before: 80 },
            children: [
                new TextRun({ text: 'Institusi: ', size: 22, font: 'Public Sans' }),
                new TextRun({ text: 'SMK Negeri / Unit Pelaksana Teknis Sarana Prasarana Sekolah', size: 22, font: 'Public Sans', bold: true })
            ]
        }),
        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { before: 80, after: 400 },
            children: [
                new TextRun({ text: 'Tanggal Dibuat: ', size: 22, font: 'Public Sans' }),
                new TextRun({ text: '2 November 2026', size: 22, font: 'Public Sans', bold: true })
            ]
        }),
        new Paragraph({ pageBreakBefore: true })
    );

    // ==========================================
    // RIWAYAT PERUBAHAN
    // ==========================================
    docChildren.push(
        heading1('Riwayat Perubahan Dokumen'),
        bodyPara('Tabel riwayat revisi dan pembaruan spesifikasi kebutuhan perangkat lunak:'),
        createStyledTable(
            ['Nama Pengubah', 'Tanggal', 'Alasan Perubahan', 'Versi'],
            [
                ['Dirya & Tim Pengembang', '20 Oktober 2026', 'Inisiasi spesifikasi kebutuhan awal modul agenda dan sarpras UKK', '0.1'],
                ['Dirya & Tim Pengembang', '24 Oktober 2026', 'Penambahan WhatsApp OTP, Dual-Approval Lab, dan Conflict Engine', '0.8'],
                ['Dirya & Tim Pengembang', '2 November 2026', 'Finalisasi dokumen SRS v1.0 disetujui (lengkap UML, Wireframe, UI/UX)', '1.0']
            ],
            [25, 20, 45, 10]
        ),
        new Paragraph({ spacing: { after: 300 } })
    );

    // ==========================================
    // DAFTAR ISI RINGKAS
    // ==========================================
    docChildren.push(
        heading1('Daftar Isi Dokumen (Table of Contents)'),
        bulletItem('1. Pendahuluan:', 'Tujuan, Definisi & Istilah, Audiens Pembaca, Ruang Lingkup Sistem, Sistem Penomoran, Referensi, Ikhtisar Dokumen.'),
        bulletItem('2. Deskripsi Umum Sistem:', 'Perspektif Perangkat Lunak, Fungsi Sistem, Karakteristik & Klasifikasi Pengguna, Lingkungan Operasi, Batasan Desain, Dokumentasi, Asumsi & Ketergantungan.'),
        bulletItem('3. Kebutuhan Antarmuka Eksternal:', 'Antarmuka Pengguna (Form F.01 s/d F.14 & L.01), Antarmuka Perangkat Keras, Antarmuka Perangkat Lunak, Antarmuka Komunikasi.'),
        bulletItem('4. Fitur Sistem (11 Modul Spesifikasi):', 'Autentikasi Sesi, WhatsApp OTP, Booking & Conflict Detection, Dual-Approval Lab, Veto Pemindahan Ruangan, Lapor Kerusakan & Kompresi Foto, Update Status PIC, Eskalasi WA 24 Jam, Kalender Publik & TV Display, Master Data, Rekapitulasi Laporan.'),
        bulletItem('5. Kebutuhan Non Fungsional Lainnya:', 'Kinerja (Throughput, Latensi), Keamanan (Bcrypt, CSRF, Throttle), Perlindungan Data (CIA Triad), Atribut Kualitas, dan 6 Aturan Bisnis.'),
        bulletItem('6. Kebutuhan Lain:', 'Penyimpanan Berkas Digital dan Jejak Rekam Audit (Audit Trail).'),
        bulletItem('Appendix A: Model Analisa (UML):', 'Use Case Diagram, Use Case Description, Activity Diagram, Sequence Diagram, Class Diagram.'),
        bulletItem('Appendix B: Desain Antarmuka Sistem:', 'Panduan Desain Semantik, Wireframe Skematis, Mockup UI Dashboard Admin, UI Kerusakan & WA, dan Layar Smart TV.'),
        bulletItem('Appendix C: Daftar Yang Akan Ditentukan Kemudian (TBD):', 'Daftar penyesuaian untuk versi masa depan (QR Code, Bot Webhook, Ekspor Excel).'),
        new Paragraph({ pageBreakBefore: true })
    );

    // ==========================================
    // BAB 1: PENDAHULUAN
    // ==========================================
    docChildren.push(
        heading1('1. Pendahuluan'),
        heading2('1.1 Tujuan'),
        bodyPara('Dokumen Spesifikasi Kebutuhan Perangkat Lunak (SKPL / SRS) ini dibuat sebagai pedoman formal dalam perancangan, implementasi, dan pengujian sistem informasi RAPI-Sekolah (Sistem Manajemen Agenda Peminjaman Ruang & Penanganan Pengaduan Fasilitas Sekolah) versi 1.0. Dokumen ini mendefinisikan seluruh kebutuhan fungsional pengguna, kebutuhan non-fungsional, batasan arsitektur sistem, model analisa UML berstandar industri, serta spesifikasi desain antarmuka pengguna (Wireframe dan UI/UX) yang diimplementasikan pada proyek UKK RPL.'),

        heading2('1.2 Definisi dan Istilah'),
        bodyPara('Guna memberikan pemahaman yang seragam di antara seluruh pemangku kepentingan (stakeholders), berikut adalah definisi istilah dan singkatan yang digunakan:'),
        createStyledTable(
            ['Istilah / Singkatan', 'Definisi & Penjelasan'],
            [
                ['RAPI-Sekolah', 'Nama perangkat lunak aplikasi terintegrasi untuk pengelolaan Ruang, Agenda, Peminjaman, dan Inventaris/Pengaduan Fasilitas Sekolah.'],
                ['SRS / SKPL', 'Software Requirements Specification / Spesifikasi Kebutuhan Perangkat Lunak standar IEEE Std 830-1998 / IEEE 1233.'],
                ['RBAC', 'Role-Based Access Control, metode pengendalian hak akses pengguna berdasarkan peran (Admin, Waka, PIC, Guru, Organisasi, Siswa).'],
                ['PIC Ruangan', 'Person in Charge, guru atau staf teknis penanggung jawab ruangan tertentu (misal: PIC Lab Komputer, PIC Sarpras).'],
                ['Waka', 'Wakil Kepala Sekolah (Sarana Prasarana / Kurikulum) yang memegang kewenangan tertinggi perizinan ruang dan hak veto pemindahan agenda.'],
                ['Dual-Approval', 'Mekanisme persetujuan ganda yang mewajibkan persetujuan dari PIC Ruangan DAN Waka/Admin (khususnya untuk peminjaman ruangan bertipe Laboratorium).'],
                ['Conflict Detection', 'Algoritma pencegahan tabrakan agenda peminjaman ruangan pada tanggal dan rentang waktu yang saling beririsan (time slot overlapping).'],
                ['WhatsApp OTP', 'One-Time Password 6 digit numerik unik sekali pakai dengan masa kedaluwarsa 10 menit untuk pemulihan kata sandi mandiri via WhatsApp.'],
                ['Eskalasi WhatsApp', 'Mekanisme pembuatan tautan wa.me berformat resmi secara otomatis ke nomor WhatsApp PIC Ruang apabila aduan kerusakan sarpras belum ditangani lebih dari 24 jam.'],
                ['Pessimistic Locking', 'Teknik transaksi database menggunakan klausul SELECT ... FOR UPDATE (lockForUpdate()) guna menjamin konsistensi data dari race condition.']
            ],
            [25, 75]
        ),
        new Paragraph({ spacing: { after: 180 } }),

        heading2('1.3 Suggestions Pembaca yang Disarankan Membaca Dokumen Ini'),
        bodyPara('Audience yang harus memahami dan menggunakan dokumen ini antara lain:'),
        bulletItem('Analis Sistem:', 'Menggunakan dokumen ini sebagai pedoman dalam membuat perancangan arsitektur dan relasi sistem.'),
        bulletItem('Programmer / Developer:', 'Memanfaatkan dokumen ini untuk mengimplementasikan fungsionalitas backend Laravel 13, frontend Blade, dan endpoint API.'),
        bulletItem('Tester / QA:', 'Menjadikan dokumen ini sebagai acuan dalam menyusun skenario pengetesan fungsional dan integritas data.'),
        bulletItem('Asesor / Penguji UKK:', 'Memverifikasi kesesuaian hasil karya perangkat lunak dengan indikator uji kompetensi keahlian.'),

        heading2('1.4 Ruang Lingkup Sistem'),
        bodyPara('Perangkat lunak RAPI-Sekolah mencakup empat pilar proses bisnis tata kelola sarpras sekolah:'),
        bulletItem('1. Peminjaman Ruangan & Agenda:', 'Pengajuan jadwal, validasi ketersediaan ruang, deteksi tabrakan waktu otomatis, pembatasan ruang kelas khusus akhir pekan, dual-approval lab, serta hak veto pemindahan ruang (reschedule) oleh Waka dengan notifikasi otomatis.'),
        bulletItem('2. Pengaduan Kerusakan Fasilitas:', 'Pelaporan kerusakan disertai kompresi foto otomatis (JPEG), tracking status perbaikan (Belum Diperbaiki, Menunggu Spare Part, Sudah Diperbaiki), riwayat catatan progres oleh PIC, dan eskalasi WhatsApp bila aduan melampaui SLA 24 jam.'),
        bulletItem('3. Kalender Publik & Smart TV Display:', 'Kalender publik read-only untuk siswa/masyarakat sekolah serta API display layar TV lobi sekolah untuk penayangan agenda real-time hari berjalan.'),
        bulletItem('4. Master Data & Rekapitulasi Laporan:', 'Manajemen pengguna, pengelolaan master ruangan & penugasan PIC, reset kata sandi via WhatsApp OTP, dan pencetakan rekapitulasi laporan resmi berstandar format PDF/print kop sekolah.'),

        heading2('1.5 Sistem Penomoran'),
        createStyledTable(
            ['Hal / Bagian', 'Aturan Penomoran', 'Contoh'],
            [
                ['Use Case', 'Nomor berbentuk UC.xx, di mana xx adalah nomor urut usecase', 'UC.01, UC.03, UC.06'],
                ['Kebutuhan Fungsional', 'Nomor berbentuk KF.xx.yy atau REQ-xx', 'KF.03.01, REQ-7'],
                ['Kebutuhan Non Fungsional', 'Nomor berbentuk KnF.xx.yy', 'KnF.01.01, KnF.02.01'],
                ['Form Antarmuka', 'Nomor berbentuk F.xx', 'F.01 (Login), F.07 (Booking)'],
                ['Laporan Keluaran', 'Nomor berbentuk L.xx', 'L.01 (Rekapitulasi Sarpras)']
            ],
            [25, 45, 30]
        ),
        new Paragraph({ spacing: { after: 180 } }),

        heading2('1.6 Referensi'),
        bulletItem('IEEE Std 830-1998:', 'IEEE Recommended Practice for Software Requirements Specifications.'),
        bulletItem('IEEE Std 1233-1998 Edition:', 'Guide for Developing System Requirements Specifications.'),
        bulletItem('Dokumentasi Resmi Laravel Framework v13.x:', 'Eloquent ORM, Routing, Sanctum, Database Transactions, and Notifications.'),
        bulletItem('Pedoman Teknis UKK SMK:', 'Skema Uji Kompetensi Keahlian Rekayasa Perangkat Lunak.'),

        heading2('1.7 Ikhtisar Dokumen'),
        bodyPara('Penulisan dokumen ini dibagi menjadi 6 bab utama serta 3 lampiran teknis (Appendix): Bab 1 Pendahuluan, Bab 2 Deskripsi Umum Sistem, Bab 3 Kebutuhan Antarmuka Eksternal, Bab 4 Fitur Sistem, Bab 5 Kebutuhan Non Fungsional, Bab 6 Kebutuhan Lain, Appendix A Model Analisa UML, Appendix B Desain Antarmuka Sistem, dan Appendix C Daftar Yang Akan Ditentukan Kemudian (TBD).')
    );

    // ==========================================
    // BAB 2: DESKRIPSI UMUM SISTEM
    // ==========================================
    docChildren.push(
        heading1('2. Deskripsi Umum Sistem'),
        heading2('2.1 Perspektif Perangkat Lunak'),
        bodyPara('RAPI-Sekolah merupakan perangkat lunak berbasis web modern yang beroperasi dengan arsitektur terpusat (client-server). Sistem ini menghubungkan seluruh unit kerja di lingkungan sekolah (Waka Sarpras, Petugas PIC Ruang/Laboratorium, Dewan Guru, Organisasi Kesiswaan/OSIS, serta Siswa). Perangkat lunak ini menyediakan dua gerbang antarmuka: antarmuka berbasis Web (Blade) untuk manajemen pengguna desktop/mobile, serta Application Programming Interface (REST API) yang diamankan dengan Laravel Sanctum untuk konsumsi data layar Smart TV lorong sekolah dan integrasi pesan WhatsApp Gateway.'),

        heading2('2.2 Fungsi Perangkat Lunak'),
        bodyPara('Fungsi-fungsi inti yang disediakan oleh perangkat lunak RAPI-Sekolah antara lain:'),
        bulletItem('1. Autentikasi Pengguna & WhatsApp OTP:', 'Login multi-role, logout aman, serta pemulihan kata sandi mandiri via OTP 6-digit WhatsApp.'),
        bulletItem('2. Peminjaman Ruangan & Anti Bentrok:', 'Pengajuan jadwal dengan validasi irisan waktu otomatis dan pembatasan peminjaman ruang kelas pada akhir pekan.'),
        bulletItem('3. Dual-Approval Laboratorium:', 'Persetujuan ganda wajib dari PIC Lab dan Admin/Waka untuk ruangan laboratorium.'),
        bulletItem('4. Veto Pemindahan Ruangan:', 'Pemindahan agenda peminjam oleh Waka dengan pencatatan alasan resmi dan notifikasi otomatis.'),
        bulletItem('5. Lapor Kerusakan & Kompresi Foto:', 'Unggah bukti foto kerusakan yang otomatis dikompresi ke format JPEG optimal.'),
        bulletItem('6. Tracking Status & Progres PIC:', 'Pembaruan status perbaikan dan penulisan catatan teknis progres penanganan oleh PIC.'),
        bulletItem('7. Eskalasi WhatsApp 24 Jam:', 'Peringatan otomatis ke nomor WhatsApp resmi PIC jika laporan belum tertangani lebih dari 24 jam.'),
        bulletItem('8. Kalender Publik & Smart TV:', 'Tampilan jadwal kegiatan transparan untuk siswa dan penayangan agenda harian pada layar TV lobi.'),
        bulletItem('9. Master Data & Rekap Cetak:', 'Manajemen akun, manajemen ruangan & penugasan PIC, serta cetak laporan PDF/print berkop resmi.'),

        heading2('2.3 Karakteristik dan Klasifikasi Pengguna'),
        createStyledTable(
            ['Kelompok Pengguna', 'Hak Akses & Kewenangan Utama', 'Karakteristik & Keterampilan'],
            [
                ['Admin / Waka', 'Kewenangan tertinggi: CRUD user & ruang, persetujuan agenda, hak veto pemindahan ruang, eskalasi WhatsApp, dan cetak seluruh laporan.', 'Menengah (Menguasai operasional administrasi web dan komputer).'],
                ['PIC Ruangan', 'Menyetujui agenda lab di ruangnya, memperbarui status perbaikan kerusakan sarpras, mengisi catatan progres teknis, dan mengajukan pinjam ruang.', 'Menengah (Staf laboratorium / sarpras kejuruan).'],
                ['Guru & Organisasi', 'Mengajukan peminjaman ruang, melaporkan kerusakan sarpras dengan foto bukti, memantau status persetujuan agenda dan status perbaikan.', 'Dasar (Dapat mengoperasikan formulir web standar).'],
                ['Siswa / Publik', 'Akses read-only: melihat kalender agenda publik dan membaca informasi pada layar Smart TV display sekolah tanpa perlu login.', 'Dasar (Akses informasi umum).']
            ],
            [22, 50, 28]
        ),
        new Paragraph({ spacing: { after: 180 } }),

        heading2('2.4 Lingkungan Operasi'),
        bodyPara('Sistem dirancang untuk beroperasi pada lingkungan:'),
        bulletItem('Sistem Operasi Server:', 'Windows 10/11 64-bit (Laragon Stack) atau Linux Ubuntu Server 22.04 LTS.'),
        bulletItem('Web Server:', 'Apache 2.4+ atau Nginx 1.24+.'),
        bulletItem('Runtime & Framework:', 'PHP versi 8.2+ atau 8.4 dengan framework Laravel 13, Sanctum, dan Intervention Image.'),
        bulletItem('Database Management System:', 'MySQL 8.0+ atau MariaDB 10.6+ dengan mesin penyimpanan InnoDB.'),
        bulletItem('Peramban Klien:', 'Google Chrome, Mozilla Firefox, Microsoft Edge, Safari pada PC, Laptop, Tablet, dan Ponsel.'),

        heading2('2.5 Batasan Desain dan Implementasi'),
        bodyPara('Perangkat lunak ini memiliki batasan implementasi sebagai berikut:'),
        bulletItem('1. Arsitektur MVC & Service Layer:', 'Seluruh logika bisnis inti ditempatkan pada EventBookingService, ComplaintStatusService, ComplaintWhatsAppService, dan WhatsAppOtpService.'),
        bulletItem('2. Concurrency Control:', 'Menggunakan transaksi DB::transaction() dengan pessimistic row locking (lockForUpdate()) untuk mencegah double-booking pada detik bersamaan.'),
        bulletItem('3. Kompresi Foto:', 'Citra foto bukti kerusakan wajib dikompresi menjadi format JPEG dengan ukuran di bawah 500 KB.'),
        bulletItem('4. Aturan Ruang Kelas:', 'Peminjaman ruangan bertipe kelas hanya diperbolehkan pada hari Sabtu atau Minggu.'),
        bulletItem('5. Dual-Approval Lab:', 'Agenda laboratorium wajib memiliki pengesahan approved_by (Admin) DAN pic_approved_by (PIC Lab).'),
        bulletItem('6. Ambang Batas Eskalasi:', 'Tombol eskalasi WhatsApp hanya aktif jika laporan berstatus Belum Diperbaiki dan berusia lebih dari 24 jam.'),

        heading2('2.6 Dokumentasi bagi Pengguna'),
        bodyPara('Sistem dilengkapi dengan User Manual digital untuk masing-masing hak akses peran, dokumen panduan backend API (PANDUAN_BACKEND_API_RAPI_SEKOLAH.md), serta pesan instruksi validasi interaktif pada setiap formulir.'),

        heading2('2.7 Asumsi dan Ketergantungan'),
        bodyPara('Asumsi yang digunakan: data nomor telepon pengguna merupakan nomor aktif yang terhubung dengan akun WhatsApp resmi. Ketergantungan: ketersediaan koneksi internet/intranet lokal sekolah yang stabil untuk konsumsi API layar Smart TV.')
    );

    // ==========================================
    // BAB 3: KEBUTUHAN ANTARMUKA EKSTERNAL
    // ==========================================
    docChildren.push(
        heading1('3. Kebutuhan Antarmuka Eksternal'),
        heading2('3.1 Antarmuka Pengguna'),
        bodyPara('Daftar seluruh antarmuka formulir dan laporan sistem RAPI-Sekolah:'),
        createStyledTable(
            ['Nomor', 'Nama Antarmuka', 'Hak Akses', 'Fungsi Antarmuka'],
            [
                ['F.01', 'Form Login & Identifikasi Akun', 'Semua Pengguna', 'Masukan username/email dan password dengan verifikasi keamanan.'],
                ['F.02', 'Form WhatsApp OTP', 'Semua Pengguna', 'Permintaan dan verifikasi 6 digit kode OTP pemulihan sandi.'],
                ['F.03', 'Form Reset Password Baru', 'Semua Pengguna', 'Masukan kata sandi baru pasca verifikasi OTP berhasil.'],
                ['F.04', 'Dashboard Admin & Waka', 'Admin & Waka', 'Statistik sarpras, jadwal agenda, tabel persetujuan, panel veto ruang.'],
                ['F.05', 'Dashboard PIC Ruangan', 'PIC Ruangan', 'Persetujuan lab, update status perbaikan sarpras & catatan progres.'],
                ['F.06', 'Dashboard Guru & Organisasi', 'Guru, OSIS', 'Pengajuan booking ruang, lapor kerusakan, tracking status.'],
                ['F.07', 'Form Pengajuan Peminjaman Ruang', 'Semua Login', 'Pilihan ruang, tanggal, jam mulai, jam selesai, nama agenda.'],
                ['F.08', 'Form Pelaporan Kerusakan Fasilitas', 'Semua Login', 'Pilihan ruang, deskripsi kerusakan, dan unggah foto dokumentasi.'],
                ['F.09', 'Modal Pemindahan Ruang (Veto)', 'Admin & Waka', 'Pilihan ruang pengganti dan masukan alasan pemindahan wajib.'],
                ['F.10', 'Modal Status Perbaikan & Catatan', 'PIC Ruangan', 'Pilihan status perbaikan sarpras dan kolom catatan teknis progres.'],
                ['F.11', 'Panel Manajemen Pengguna', 'Admin & Waka', 'CRUD data pengguna sekolah dan fasilitas reset password cepat.'],
                ['F.12', 'Form Edit Profil Akun', 'Semua Login', 'Perbaruan nama, nomor WhatsApp, email, dan kata sandi.'],
                ['F.13', 'Kalender Publik Agenda Sekolah', 'Publik & Siswa', 'Tampilan kalender interaktif bulanan/harian read-only.'],
                ['F.14', 'Antarmuka Layar Smart TV Lobi', 'Smart TV Display', 'Tampilan layar penuh jadwal kegiatan hari ini dan pengumuman.'],
                ['L.01', 'Laporan Rekapitulasi Sarpras', 'Admin, Waka, PIC', 'Dokumen cetak rekapitulasi agenda dan kerusakan format print/PDF.']
            ],
            [10, 30, 22, 38]
        ),
        new Paragraph({ spacing: { after: 180 } }),

        heading2('3.2 Antarmuka Perangkat Keras'),
        bulletItem('Komputer Server:', 'Processor Multi-Core 2.0 GHz+, RAM minimal 4 GB (disarankan 8 GB), SSD minimal 20 GB free space, Gigabit LAN.'),
        bulletItem('Komputer Klien / Laptop:', 'Processor Intel/AMD setara Core i3, RAM minimal 2 GB, layar monitor resolusi 1366x768+.'),
        bulletItem('Perangkat Mobile:', 'Smartphone Android (OS 8.0+) atau iOS (13.0+) berkoneksi internet dan terinstal WhatsApp.'),
        bulletItem('Layar Smart TV Lobi:', 'Smart TV atau Mini PC Android beresolusi Full HD 1080p (1920x1080) terhubung HDMI/Wi-Fi.'),

        heading2('3.3 Antarmuka Perangkat Lunak'),
        bulletItem('Sistem Operasi:', 'Windows 10/11 64-bit atau Linux Ubuntu Server 22.04 LTS.'),
        bulletItem('Web Server & DBMS:', 'Nginx 1.24+ / Apache 2.4+ dan MySQL 8.0+ InnoDB.'),
        bulletItem('Framework & Bahasa:', 'PHP 8.2+ dengan Laravel 13, Sanctum API, dan Intervention Image.'),

        heading2('3.4 Antarmuka Komunikasi'),
        bulletItem('Protokol HTTP/HTTPS:', 'Port 80 dan 443 dengan enkripsi SSL/TLS.'),
        bulletItem('RESTful JSON API:', 'Pertukaran data asinkron pada endpoint kalender publik dan Smart TV display.'),
        bulletItem('Universal WhatsApp Protocol:', 'Protokol tautan https://wa.me/{nomor}?text={pesan} untuk eskalasi insiden.')
    );

    // ==========================================
    // BAB 4: FITUR SISTEM
    // ==========================================
    docChildren.push(
        heading1('4. Fitur Sistem'),
        bodyPara('Berikut adalah rincian kebutuhan fungsional perangkat lunak RAPI-Sekolah:'),

        heading2('4.1 Fitur 1: Autentikasi Pengguna & Manajemen Sesi'),
        bodyPara('Deskripsi & Prioritas: Mengatur otentikasi login multi-role, proteksi sesi, dan logout aman. Prioritas: Tinggi.'),
        bodyPara('Stimulus/Respons: Pengguna memasukkan username dan password pada Form F.01. Jika cocok dengan hash Bcrypt, sesi dibuat dan diarahkan ke dashboard perannya. Jika gagal, pesan kesalahan dimunculkan. Pengguna klik Logout, sesi dihapus.'),
        bulletItem('REQ-1 (KF.01.01):', 'Sistem harus memvalidasi data masukan username dan password sebelum mengizinkan akses.'),
        bulletItem('REQ-2 (KF.01.02):', 'Sistem harus membatasi hak akses URL berdasarkan role melalui middleware EnsureRole.'),
        bulletItem('REQ-3 (KF.01.03):', 'Sistem harus menghapus seluruh token dan sesi aktif saat aksi logout dilakukan.'),

        heading2('4.2 Fitur 2: Pemulihan Kata Sandi Mandiri via WhatsApp OTP'),
        bodyPara('Deskripsi & Prioritas: Fasilitas pemulihan kata sandi mandiri menggunakan kode OTP 6 digit via WhatsApp. Prioritas: Sedang.'),
        bodyPara('Stimulus/Respons: Pengguna memasukkan username pada Form F.02. Sistem membuat OTP 6 digit acak (berlaku 10 menit) dan mengirimkannya ke nomor WhatsApp pengguna. Pengguna memasukkan OTP, sistem memverifikasi, dan membuka form password baru (F.03).'),
        bulletItem('REQ-4 (KF.02.01):', 'Sistem menghasilkan kode OTP 6 digit unik berbasis generator acak kriptografis (random_int(100000, 999999)).'),
        bulletItem('REQ-5 (KF.02.02):', 'Sistem menetapkan batas kedaluwarsa OTP 10 menit dan membatasi rate limit 5 permohonan per menit.'),
        bulletItem('REQ-6 (KF.02.03):', 'Sistem mengenkripsi kata sandi baru menggunakan Bcrypt sebelum disimpan ke tabel users.'),

        heading2('4.3 Fitur 3: Pengajuan Peminjaman Ruang & Deteksi Tabrakan Waktu'),
        bodyPara('Deskripsi & Prioritas: Mengajukan peminjaman ruang dengan validasi ketersediaan waktu bebas bentrok. Prioritas: Tinggi.'),
        bodyPara('Stimulus/Respons: Pengguna mengisi Form F.07. Sistem memeriksa ketersediaan ruang secara transaksional: apakah ada agenda yang beririsan waktu pada ruang dan tanggal tersebut. Jika bentrok, sistem menolak. Jika bebas bentrok, disimpan berstatus pending.'),
        bulletItem('REQ-7 (KF.03.01):', 'Sistem memvalidasi agar jam selesai peminjaman selalu lebih besar dari jam mulai (end_time > start_time).'),
        bulletItem('REQ-8 (KF.03.02):', 'Sistem menolak permohonan jika terdapat irisan waktu (start_baru < end_lama DAN end_baru > start_lama) pada agenda aktif.'),
        bulletItem('REQ-9 (KF.03.03):', 'Sistem menolak peminjaman ruangan bertipe kelas pada hari kerja efektif (Senin s/d Jumat).'),
        bulletItem('REQ-10 (KF.03.04):', 'Sistem wajib menggunakan transaksi pesimis (lockForUpdate()) selama validasi ketersediaan ruang.'),

        heading2('4.4 Fitur 4: Verifikasi & Persetujuan Bertingkat (Dual-Approval Lab)'),
        bodyPara('Deskripsi & Prioritas: Pengesahan agenda peminjaman ruang, di mana laboratorium mewajibkan persetujuan ganda dari PIC Lab dan Admin/Waka. Prioritas: Tinggi.'),
        bodyPara('Stimulus/Respons: Ruang umum langsung aktif berstatus approved saat disetujui Admin/Waka. Ruang laboratorium mewajibkan pengisian pic_approved_by oleh PIC Lab DAN approved_by oleh Admin/Waka sebelum berubah menjadi approved. Penolakan salah satu pihak mengubah status menjadi rejected.'),
        bulletItem('REQ-11 (KF.04.01):', 'Sistem membedakan alur persetujuan berdasarkan atribut rooms.room_type.'),
        bulletItem('REQ-12 (KF.04.02):', 'Agenda laboratorium hanya berstatus approved jika approved_by dan pic_approved_by keduanya terisi.'),
        bulletItem('REQ-13 (KF.04.03):', 'Hanya PIC terdaftar pada laboratorium bersangkutan yang berwenang memberikan pengesahan PIC Lab.'),

        heading2('4.5 Fitur 5: Pemindahan Ruangan / Hak Veto Waka dengan Notifikasi Otomatis'),
        bodyPara('Deskripsi & Prioritas: Hak prerogatif Waka/Admin untuk memindahkan agenda peminjam ke ruang lain disertai alasan resmi dan notifikasi otomatis. Prioritas: Tinggi.'),
        bodyPara('Stimulus/Respons: Waka memilih agenda pada Form F.09, memilih ruang pengganti, dan menginputkan alasan (move_reason). Sistem memvalidasi ketersediaan ruang baru, mengubah ruang agenda, dan menerbitkan notifikasi otomatis ke akun peminjam.'),
        bulletItem('REQ-14 (KF.05.01):', 'Hak veto pemindahan ruangan hanya dapat dieksekusi oleh pengguna ber-role Admin atau Waka.'),
        bulletItem('REQ-15 (KF.05.02):', 'Sistem mewajibkan pengisian alasan pemindahan (move_reason) maksimal 500 karakter.'),
        bulletItem('REQ-16 (KF.05.03):', 'Sistem memicu kelas notifikasi EventMovedNotification ke tabel notifications peminjam.'),

        heading2('4.6 Fitur 6: Pelaporan Kerusakan Sarana Prasarana dengan Kompresi Foto Bukti'),
        bodyPara('Deskripsi & Prioritas: Pelaporan sarpras rusak disertai unggah foto bukti visual yang otomatis dikompresi. Prioritas: Tinggi.'),
        bodyPara('Stimulus/Respons: Pengguna mengisi Form F.08 (ruang, deskripsi kerusakan, file foto). Sistem mengompresi gambar menjadi JPEG via Intervention Image dan menyimpan baris aduan baru berstatus Belum Diperbaiki.'),
        bulletItem('REQ-17 (KF.06.01):', 'Sistem menyediakan form pelaporan kerusakan yang mewajibkan input ruang, deskripsi, dan foto bukti.'),
        bulletItem('REQ-18 (KF.06.02):', 'Sistem mengonversi dan mengompresi foto bukti ke format JPEG terstandarisasi sebelum disimpan ke disk.'),
        bulletItem('REQ-19 (KF.06.03):', 'Sistem otomatis mencatat waktu pelaporan pada kolom reported_at menggunakan waktu server.'),

        heading2('4.7 Fitur 7: Manajemen Status Perbaikan Fasilitas & Catatan Progres oleh PIC'),
        bodyPara('Deskripsi & Prioritas: PIC Ruang memperbarui status penanganan perbaikan dan menginputkan catatan progres teknis. Prioritas: Tinggi.'),
        bodyPara('Stimulus/Respons: PIC membuka Form F.10 pada aduan di ruangnya, memilih status (Belum Diperbaiki, Menunggu Spare Part, Sudah Diperbaiki), mengisikan progress_note, dan menyimpannya ke sistem.'),
        bulletItem('REQ-20 (KF.07.01):', 'Pembaruan status perbaikan hanya diizinkan untuk PIC yang ditugaskan pada ruangan tersebut.'),
        bulletItem('REQ-21 (KF.07.02):', 'Sistem menyediakan tiga opsi status baku: Belum Diperbaiki, Menunggu Spare Part, Sudah Diperbaiki.'),
        bulletItem('REQ-22 (KF.07.03):', 'Sistem mencatat identitas penangan pada kolom handler_id dan menyimpan catatan progress_note.'),

        heading2('4.8 Fitur 8: Eskalasi Pengaduan Kerusakan Kritis via WhatsApp PIC Ruangan'),
        bodyPara('Deskripsi & Prioritas: Pengawasan Waka/Admin untuk mengeskalasikan aduan kerusakan yang tertunda lebih dari 24 jam langsung ke WhatsApp PIC Ruang. Prioritas: Sedang.'),
        bodyPara('Stimulus/Respons: Jika aduan berstatus Belum Diperbaiki dan waktu lapor melebihi 24 jam, tombol eskalasi aktif. Waka mengklik tombol, sistem membuat format pesan peringatan resmi dan membuka tautan wa.me ke kontak PIC.'),
        bulletItem('REQ-23 (KF.08.01):', 'Tombol eskalasi WhatsApp hanya aktif jika laporan berstatus Belum Diperbaiki dan berusia > 24 jam.'),
        bulletItem('REQ-24 (KF.08.02):', 'Sistem memvalidasi dan memformat nomor telepon PIC ke format internasional diawali kode negara 62.'),
        bulletItem('REQ-25 (KF.08.03):', 'Pesan eskalasi baku memuat nomor tiket laporan, nama ruangan, waktu lapor, dan deskripsi kerusakan.'),

        heading2('4.9 Fitur 9: Kalender Publik Read-Only & Smart TV Display Agenda Sekolah Real-Time'),
        bodyPara('Deskripsi & Prioritas: Tampilan agenda sekolah transparan untuk siswa/publik serta antarmuka layar TV lobi sekolah. Prioritas: Sedang.'),
        bodyPara('Stimulus/Respons: Siswa mengakses /calendar (F.13) tanpa login untuk melihat agenda yang telah disetujui. Layar Smart TV mengakses endpoint /api/tv-display/agenda (F.14) untuk menampilkan jadwal ruang hari berjalan secara real-time.'),
        bulletItem('REQ-26 (KF.09.01):', 'Kalender publik bersifat read-only dan dapat diakses bebas tanpa login.'),
        bulletItem('REQ-27 (KF.09.02):', 'Kalender publik hanya menampilkan agenda yang telah berstatus approved atau moved.'),
        bulletItem('REQ-28 (KF.09.03):', 'Sistem menyediakan endpoint API /api/tv-display/agenda yang mengembalikan JSON agenda hari ini secara cepat.'),

        heading2('4.10 Fitur 10: Manajemen Master Data Pengguna & Ruangan'),
        bodyPara('Deskripsi & Prioritas: Pengelolaan akun pengguna dan master data ruangan beserta penunjukan PIC. Prioritas: Tinggi.'),
        bodyPara('Stimulus/Respons: Admin mengelola akun pengguna (tambah, edit, hapus, reset password) pada Form F.11 dan master ruangan (nama ruang, tipe, kapasitas, lokasi, dan penunjukan PIC).'),
        bulletItem('REQ-29 (KF.10.01):', 'CRUD master data pengguna dan ruangan hanya dapat diakses oleh Admin atau Waka.'),
        bulletItem('REQ-30 (KF.10.02):', 'Sistem menjamin keunikan username akun pengguna pada basis data.'),
        bulletItem('REQ-31 (KF.10.03):', 'Klasifikasi tipe ruangan: kelas, laboratorium, ruang_rapat, ibadah, aula, lainnya.'),
        bulletItem('REQ-32 (KF.10.04):', 'Setiap ruangan wajib menunjuk satu pengguna ber-role PIC sebagai penanggung jawab.'),

        heading2('4.11 Fitur 11: Rekapitulasi & Cetak Laporan Resmi'),
        bodyPara('Deskripsi & Prioritas: Penyusunan rekapitulasi data peminjaman ruang dan riwayat kerusakan fasilitas dalam format berkop resmi sekolah. Prioritas: Sedang.'),
        bodyPara('Stimulus/Respons: Pengguna mengklik tombol Cetak Laporan (L.01). Sistem menyaring data sesuai hak akses (Admin melihat seluruh data, PIC melihat data ruangnya, Guru melihat data pribadinya) dan menyajikannya dalam format siap cetak (PDF/print).'),
        bulletItem('REQ-33 (KF.11.01):', 'Format cetak laporan menyembunyikan navigasi melalui aturan stylesheet media print (@media print).'),
        bulletItem('REQ-34 (KF.11.02):', 'Sistem memfilter cakupan data laporan secara otomatis sesuai otorisasi pengguna aktif.'),
        bulletItem('REQ-35 (KF.11.03):', 'Laporan mencakup tabel agenda peminjaman dan tabel kerusakan sarpras berkop resmi sekolah.')
    );

    // ==========================================
    // BAB 5: KEBUTUHAN NON FUNGSIONAL LAINNYA
    // ==========================================
    docChildren.push(
        heading1('5. Kebutuhan Non Fungsional Lainnya'),
        heading2('5.1 Kebutuhan Kinerja'),
        bulletItem('KnF.01.01 (Waktu Respons):', 'Halaman aplikasi dan dashboard harus dimuat dalam waktu kurang dari 2,0 detik pada kondisi beban normal.'),
        bulletItem('KnF.01.02 (Throughput):', 'Sistem mampu melayani minimal 50 transaksi booking konkuren tanpa kegagalan integritas (zero deadlock).'),
        bulletItem('KnF.01.03 (Optimasi Gambar):', 'Kompresi citra foto bukti kerusakan menghasilkan ukuran di bawah 500 KB per gambar.'),
        bulletItem('KnF.01.04 (Respon API TV):', 'Latensi respon API endpoint /api/tv-display/agenda rata-rata di bawah 200 milidetik.'),

        heading2('5.2 Kebutuhan Keamanan'),
        bulletItem('KnF.02.01 (Enkripsi Password):', 'Seluruh kata sandi dienkripsi searah menggunakan Bcrypt dengan work factor minimal 12 putaran.'),
        bulletItem('KnF.02.02 (Proteksi CSRF):', 'Seluruh pengiriman formulir POST, PUT, DELETE wajib menyertakan verifikasi token CSRF (@csrf).'),
        bulletItem('KnF.02.03 (Proteksi SQL Injection):', 'Akses basis data wajib menggunakan teknik parameterized query dan binding Eloquent ORM.'),
        bulletItem('KnF.02.04 (Rate Limiting):', 'Endpoint login dan permintaan WhatsApp OTP diproteksi pembatasan 5 percobaan per menit per IP.'),
        bulletItem('KnF.02.05 (Validasi Unggahan):', 'File foto bukti divalidasi MIME-type (image/jpeg, image/png, image/webp) dan ukuran maksimal 5 MB.'),

        heading2('5.3 Kebutuhan Perlindungan Keamanan (Security Protection)'),
        bulletItem('Kerahasiaan (Confidentiality):', 'Informasi akun, nomor telepon, dan status agenda yang ditolak hanya dapat diakses oleh pihak berwenang.'),
        bulletItem('Integritas (Integrity):', 'Integritas data jadwal dijamin oleh transaksi pesimis (lockForUpdate()) dan foreign key constraints InnoDB.'),
        bulletItem('Ketersediaan (Availability):', 'Sistem dirancang memiliki ketersediaan (uptime) minimal 99% selama jam operasional sekolah.'),

        heading2('5.4 Atribut Kualitas Perangkat Lunak'),
        bulletItem('Reliabilitas:', 'Penanganan eksepsi terpusat mencegah kebocoran stack trace teknis kepada pengguna umum.'),
        bulletItem('Usabilitas:', 'Tata letak ergonomis dengan tipografi Google Fonts Bricolage Grotesque & Public Sans serta pesan kesalahan terpadu.'),
        bulletItem('Maintainabilitas:', 'Kode sumber modular memisahkan logika bisnis ke dalam Service Layer terstruktur.'),
        bulletItem('Portabilitas:', 'Berbasis web murni yang kompatibel penuh pada seluruh browser desktop, tablet, dan smartphone.'),

        heading2('5.5 Aturan Bisnis (Business Rules)'),
        createStyledTable(
            ['Kode', 'Nama Aturan Bisnis', 'Deskripsi & Implikasi Teknis'],
            [
                ['BR-01', 'Aturan Peminjaman Ruang Kelas', 'Peminjaman ruangan bertipe kelas hanya diperbolehkan pada hari Sabtu atau Minggu (isWeekend() == true).'],
                ['BR-02', 'Otorisasi Ganda Laboratorium', 'Ruangan laboratorium wajib memperoleh persetujuan ganda dari PIC Lab dan Admin/Waka.'],
                ['BR-03', 'Pencegahan Tabrakan Jadwal', 'Dua agenda bentrok jika irisan waktu terjadi: Mulai_lama < Selesai_baru DAN Selesai_lama > Mulai_baru (status rejected diabaikan).'],
                ['BR-04', 'Hak Veto & Notifikasi Waka', 'Waka/Admin berhak memindahkan ruangan agenda peminjam dengan alasan wajib (move_reason), memicu notifikasi otomatis.'],
                ['BR-05', 'Delegasi Penanganan PIC', 'Hanya PIC yang ditugaskan pada ruangan terkait yang berhak mengubah status perbaikan sarpras dan menginputkan catatan progres.'],
                ['BR-06', 'SLA Eskalasi 24 Jam', 'Aduan Belum Diperbaiki yang umurnya melampaui 24 jam dapat dieskalasi ke WhatsApp PIC oleh Admin/Waka.']
            ],
            [12, 35, 53]
        ),
        new Paragraph({ spacing: { after: 180 } })
    );

    // ==========================================
    // BAB 6: KEBUTUHAN LAIN
    // ==========================================
    docChildren.push(
        heading1('6. Kebutuhan Lain'),
        bulletItem('Penyimpanan Berkas Digital:', 'Berkas foto bukti dokumentasi kerusakan disimpan pada direktori lokal storage/app/public/complaints dengan tautan simbolik publik.'),
        bulletItem('Pencatatan Jejak Audit (Audit Trail):', 'Peristiwa penting (perubahan status perbaikan, pemindahan agenda oleh Waka, permohonan OTP) dicatat dalam berkas log harian laravel.log.')
    );

    // ==========================================
    // APPENDIX A: MODEL ANALISA (UML DIAGRAMS)
    // ==========================================
    docChildren.push(
        new Paragraph({ pageBreakBefore: true }),
        heading1('Appendix A: Model Analisa (UML Diagrams)'),
        heading2('A.1 Use Case Diagram & Relasi Aktor'),
        bodyPara('Model Use Case RAPI-Sekolah menghubungkan 4 aktor utama (Admin/Waka, PIC Ruang, Guru/Organisasi, dan Siswa/Publik) dengan 12 use case sistem:'),
        createStyledTable(
            ['Aktor Pengguna', 'Daftar Use Case yang Dapat Diakses', 'Relasi Khusus'],
            [
                ['Admin / Waka', 'UC.01 (Login), UC.03 (Booking), UC.05 (Approve), UC.06 (Veto Move), UC.07 (Lapor Kerusakan), UC.09 (Eskalasi WA), UC.11 (CRUD User & Ruang), UC.12 (Cetak Laporan)', 'Memiliki hak veto pemindahan ruang dan eskalasi WA.'],
                ['PIC Ruang', 'UC.01 (Login), UC.03 (Booking), UC.05 (Approve Lab), UC.07 (Lapor Kerusakan), UC.08 (Update Status & Catatan), UC.12 (Cetak Laporan)', 'Menangani perbaikan fasilitas ruang di bawah tanggung jawabnya.'],
                ['Guru / Organisasi', 'UC.01 (Login), UC.02 (WA OTP), UC.03 (Booking), UC.07 (Lapor Kerusakan), UC.12 (Cetak Laporan)', 'Peminjam ruang dan pelapor fasilitas rusak.'],
                ['Siswa / Publik', 'UC.02 (WA OTP), UC.10 (Lihat Kalender Publik & Layar Smart TV Display)', 'Akses read-only tanpa login.']
            ],
            [22, 53, 25]
        ),
        new Paragraph({ spacing: { after: 180 } }),

        heading2('A.2 Use Case Description Terperinci'),
        bodyPara('Spesifikasi rinci Use Case Inti UC.03 (Pengajuan Peminjaman Ruang):'),
        createStyledTable(
            ['Atribut Use Case', 'Spesifikasi Skenario UC.03'],
            [
                ['Nama Use Case', 'Mengajukan Peminjaman Ruangan & Agenda Sekolah (UC.03)'],
                ['Aktor Utama', 'Guru, Pengurus Organisasi (OSIS), PIC Ruangan, Admin/Waka'],
                ['Pre-Kondisi', 'Pengguna telah berhasil login ke sistem dan ruangan berstatus aktif (is_active = true).'],
                ['Post-Kondisi', 'Agenda permohonan baru tersimpan di tabel events dengan status pending dan menunggu persetujuan.'],
                ['Alur Utama (Main Flow)', '1. Aktor membuka form booking F.07.\n2. Aktor memilih ruang, tanggal, jam mulai, jam selesai, nama kegiatan.\n3. Aktor menekan tombol "Ajukan Peminjaman".\n4. Sistem memeriksa validasi weekend untuk ruang kelas.\n5. Sistem mengunci baris DB dan memeriksa irisan waktu.\n6. Sistem memvalidasi tidak ada bentrok jadwal.\n7. Sistem menyimpan agenda berstatus pending.\n8. Sistem menampilkan pesan sukses di dashboard.'],
                ['Alur Alternatif (Alternative Flow)', '4a. Ruang kelas dipilih pada hari kerja: Sistem menolak dan memunculkan pesan "Peminjaman ruang kelas hanya diperbolehkan pada hari Sabtu atau Minggu."\n5a. Terdeteksi irisan waktu (bentrok): Sistem membatalkan transaksi dan menampilkan pesan "Sorry, this room and time slot are already booked or conflict with another agenda."']
            ],
            [25, 75]
        ),
        new Paragraph({ spacing: { after: 180 } }),

        heading2('A.3 Activity Diagram (Alur Proses Sistem)'),
        bulletItem('1. Alur Peminjaman Ruangan & Deteksi Bentrok:', 'Mulai -> Buka Form F.07 -> Input Ruang, Tanggal, Jam -> Submit Booking -> Kunci Transaksi DB (lockForUpdate) -> Cek Tipe Ruang Kelas vs Hari Weekend -> Cek Irisan Waktu -> [Jika Bentrok: Batalkan & Tampilkan Pesan Error] -> [Jika Bebas Bentrok: Simpan Status Pending] -> Cek Tipe Lab -> [Jika Non-Lab: Tunggu Persetujuan Admin -> Status Approved] -> [Jika Lab: Butuh Dual-Approval PIC Lab & Waka -> Kedua Pihak Setuju -> Status Approved] -> Selesai.'),
        bulletItem('2. Alur Pelaporan Kerusakan & Eskalasi WhatsApp:', 'Mulai -> Buka Form F.08 -> Input Ruang, Deskripsi, Foto Bukti -> Kirim Laporan -> Server Kompresi Foto ke JPEG (Intervention Image) -> Simpan Tabel Complaints Status Belum Diperbaiki -> Tampil di Dashboard PIC -> PIC Tindak Lanjut: Update Status (Menunggu Spare Part / Sudah Diperbaiki) + Catatan Progres -> Selesai. Jika Laporan > 24 Jam Belum Diperbaiki -> Tombol Eskalasi Aktif -> Waka Klik Tombol -> Generate Link wa.me Resmi -> Kirim Pesan ke WhatsApp PIC Ruangan.'),

        heading2('A.4 Sequence Diagram (Interaksi Objek)'),
        bodyPara('Interaksi objek pada pengajuan peminjaman ruang:'),
        bulletItem('Langkah 1:', 'Peminjam menginput data ruang, tanggal, jam mulai (08:00), jam selesai (10:00), dan nama acara di browser.'),
        bulletItem('Langkah 2:', 'Browser mengirimkan POST /events beserta token CSRF ke EventController.'),
        bulletItem('Langkah 3:', 'EventController memanggil EventBookingService::store(user, data).'),
        bulletItem('Langkah 4:', 'EventBookingService memulai transaksi DB::transaction() dan mengunci data ruang dengan Room::lockForUpdate().'),
        bulletItem('Langkah 5:', 'EventBookingService mengeksekusi kueri pengecekan irisan waktu (start_time < end_baru AND end_time > start_baru).'),
        bulletItem('Langkah 6:', 'Database mengembalikan count = 0 (bebas bentrok).'),
        bulletItem('Langkah 7:', 'EventBookingService membuat entri Event::create() berstatus pending.'),
        bulletItem('Langkah 8:', 'Database melakukan commit transaksi, EventController mengarahkan kembali ke dashboard dengan pesan sukses.'),

        heading2('A.5 Class Diagram (Struktur Entitas Basis Data)'),
        createStyledTable(
            ['Nama Model', 'Atribut Utama', 'Relasi / Kardinalitas'],
            [
                ['Role', 'id, role_name, created_at, updated_at', 'HasMany User (1 : N)'],
                ['User', 'id, username, email, password, fullname, phone_number, otp_code, otp_expires_at, role_id', 'BelongsTo Role, HasMany Room (PIC), HasMany Event, HasMany Complaint'],
                ['Room', 'id, room_name, room_type, capacity, location, pic_id, is_active', 'BelongsTo User (PIC), HasMany Event, HasMany Complaint'],
                ['Event', 'id, room_id, requester_id, pic_id, event_name, date, start_time, end_time, status, approved_by, pic_approved_by, move_reason', 'BelongsTo Room, BelongsTo User (requester, approver, picApprover)'],
                ['Complaint', 'id, room_id, reporter_id, description, photo_path, status_repair, progress_note, handler_id, reported_at', 'BelongsTo Room, BelongsTo User (reporter, handler)'],
                ['Notification', 'id (UUID), type, notifiable_type, notifiable_id, data, read_at, created_at', 'Polymorphic BelongsTo User']
            ],
            [15, 45, 40]
        ),
        new Paragraph({ spacing: { after: 180 } })
    );

    // ==========================================
    // APPENDIX B: DESAIN ANTARMUKA SISTEM
    // ==========================================
    const imgDir = path.resolve('docs/images');
    const imgBooking = path.join(imgDir, 'rapi_booking_form_wireframe_1793593264236.jpg');
    const imgDashboard = path.join(imgDir, 'rapi_admin_dashboard_ui_1793593228970.jpg');
    const imgComplaint = path.join(imgDir, 'rapi_complaint_escalation_ui_1793593292475.jpg');
    const imgTv = path.join(imgDir, 'rapi_calendar_tv_display_ui_1793593315195.jpg');

    docChildren.push(
        new Paragraph({ pageBreakBefore: true }),
        heading1('Appendix B: Desain Antarmuka Sistem (Wireframe & UI/UX)'),
        heading2('B.1 Panduan Desain & Visual Tokens'),
        bodyPara('Desain antarmuka RAPI-Sekolah mengedepankan keterbacaan tinggi, konsistensi token warna, dan hirarki visual modern:'),
        bulletItem('Tipografi Display:', 'Google Fonts Bricolage Grotesque (Bold 700/800) untuk tajuk utama yang berani dan profesional.'),
        bulletItem('Tipografi Konten:', 'Google Fonts Public Sans (Regular 400, Medium 500, Semi-bold 600) untuk teks isi yang nyaman dibaca.'),
        bulletItem('Palet Warna Institusi:', 'Deep Forest Green (#123C33) sebagai identitas primer, Medium Emerald (#1B5246) untuk bilah samping, Warm Amber (#F6B800) untuk tombol aksi sorotan, dan Off-White Paper (#F4F7F5) untuk latar belakang kanvas.'),

        heading2('B.2 Wireframe Skematis Formulir Booking & Deteksi Bentrok'),
        bodyPara('Blueprint skematik modal pengajuan peminjaman ruangan yang dilengkapi dengan komponen alert peringatan deteksi tabrakan waktu:'),
        ...createImagePara(imgBooking, 'Gambar B.1: Wireframe Skematik Formulir Booking Ruang dengan Alert Deteksi Bentrok Waktu.'),

        heading2('B.3 Antarmuka Dashboard Administrator & Waka'),
        bodyPara('Tampilan antarmuka beresolusi tinggi untuk Dashboard Administrator dan Waka, yang menampilkan metrik statistik sarpras, jadwal peminjaman ruang, tabel persetujuan agenda, serta pelacakan perbaikan fasilitas:'),
        ...createImagePara(imgDashboard, 'Gambar B.2: Antarmuka Dashboard Administrator & Waka RAPI-Sekolah.'),

        heading2('B.4 Antarmuka Penanganan Kerusakan Fasilitas & Eskalasi WhatsApp'),
        bodyPara('Tampilan kartu aduan fasilitas rusak yang dilengkapi foto bukti, status penanganan perbaikan (Belum Diperbaiki, Menunggu Spare Part, Sudah Diperbaiki), catatan progres kerja, serta tombol eskalasi langsung ke WhatsApp PIC Ruangan:'),
        ...createImagePara(imgComplaint, 'Gambar B.3: Antarmuka Kartu Pengaduan Kerusakan dengan Tombol WhatsApp Escalation dan Modal Status.'),

        heading2('B.5 Antarmuka Smart TV Display Lobi & Kalender Publik'),
        bodyPara('Tampilan layar penuh (digital signage) untuk Smart TV di lobi dan koridor sekolah yang menyajikan jadwal agenda penggunaan ruang hari ini secara langsung, dilengkapi jam real-time dan papan pengumuman berjalan:'),
        ...createImagePara(imgTv, 'Gambar B.4: Antarmuka Smart TV Display Penayangan Jadwal Ruang Real-Time di Lobi Sekolah.')
    );

    // ==========================================
    // APPENDIX C: DAFTAR TBD
    // ==========================================
    docChildren.push(
        new Paragraph({ pageBreakBefore: true }),
        heading1('Appendix C: Daftar Yang Akan Ditentukan Kemudian (TBD)'),
        bodyPara('Daftar butir fitur dan penyesuaian lanjutan yang disiapkan untuk pengembangan versi mendatang:'),
        createStyledTable(
            ['No', 'Referensi', 'Topik / Fitur', 'Deskripsi Kebutuhan Lanjutan', 'Target'],
            [
                ['1', 'TBD-01', 'Integrasi Barcode / QR Code Pintu Ruangan', 'Penempelan stiker QR Code unik pada setiap pintu ruangan agar siswa/guru dapat memindai via smartphone untuk melihat agenda ruang aktif.', 'Versi 1.1'],
                ['2', 'TBD-02', 'WhatsApp Auto-Bot Webhook', 'Otomatisasi pengiriman pesan notifikasi agenda disetujui atau dipindahkan langsung via WhatsApp Bot API tanpa buka browser.', 'Versi 1.2'],
                ['3', 'TBD-03', 'Ekspor Laporan Format Excel (.xlsx)', 'Penyediaan opsi unduhan rekapitulasi agenda dan kerusakan fasilitas ke dalam format spreadsheet Excel selain PDF.', 'Versi 1.1'],
                ['4', 'TBD-04', 'Modul Estimasi Biaya Pemeliharaan', 'Penambahan kalkulasi nominal estimasi biaya perbaikan dan penggantian suku cadang sarana prasarana sekolah yang rusak.', 'Versi 2.0']
            ],
            [8, 12, 28, 42, 10]
        ),
        new Paragraph({ spacing: { after: 300 } }),
        noteBox('PENGESAHAN DOKUMEN', 'Dokumen Spesifikasi Kebutuhan Perangkat Lunak ini telah ditinjau dan dinyatakan disetujui sebagai acuan utama pengerjaan Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak tahun ajaran 2026/2027.')
    );

    // ==========================================
    // BUILD DOCUMENT
    // ==========================================
    const doc = new Document({
        styles: {
            default: {
                document: {
                    run: {
                        font: 'Public Sans',
                        size: 22,
                        color: '1A231F'
                    }
                }
            },
            heading1: {
                run: {
                    font: 'Bricolage Grotesque',
                    size: 32,
                    bold: true,
                    color: PRIMARY_COLOR
                }
            },
            heading2: {
                run: {
                    font: 'Bricolage Grotesque',
                    size: 26,
                    bold: true,
                    color: ACCENT_COLOR
                }
            },
            heading3: {
                run: {
                    font: 'Public Sans',
                    size: 22,
                    bold: true,
                    color: '2C433B'
                }
            }
        },
        sections: [
            {
                properties: {
                    page: {
                        margin: {
                            top: 1440,    // 1 inch
                            bottom: 1440, // 1 inch
                            left: 1440,   // 1 inch
                            right: 1440   // 1 inch
                        }
                    }
                },
                headers: {
                    default: new Header({
                        children: [createHeaderPara('RAPI-Sekolah — Dokumen Spesifikasi Kebutuhan Perangkat Lunak (SRS v1.0)')]
                    })
                },
                footers: {
                    default: new Footer({
                        children: [createFooterPara()]
                    })
                },
                children: docChildren
            }
        ]
    });

    const buffer = await Packer.toBuffer(doc);
    const targetFileRoot = path.resolve('SRS_RAPI_SEKOLAH_V1.0.docx');
    const targetFileDocs = path.resolve('docs/SRS_RAPI_SEKOLAH_V1.0.docx');

    fs.writeFileSync(targetFileRoot, buffer);
    fs.writeFileSync(targetFileDocs, buffer);

    console.log(`Berhasil membuat dokumen Word di:\n1. ${targetFileRoot}\n2. ${targetFileDocs}`);
}

generate().catch(err => {
    console.error('Terjadi kesalahan:', err);
    process.exit(1);
});
