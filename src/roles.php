<?php
const ROLE_STUDENT = 'student';
const ROLE_CLERK = 'clerk';
const ROLE_CHAIR = 'chair';
const ROLE_MANAGER = 'manager';
const ROLE_EXECUTIVE = 'executive';

const ROLE_LABELS = [
    ROLE_STUDENT => 'Ogrenci',
    ROLE_CLERK => 'Memur',
    ROLE_CHAIR => 'Bolum Baskani',
    ROLE_MANAGER => 'Yonetici',
    ROLE_EXECUTIVE => 'Ust Yonetici',
];

const USER_STATUS_PENDING = 'pending';
const USER_STATUS_ACTIVE = 'active';
const USER_STATUS_DISABLED = 'disabled';

const APPLICATION_STATUS_DRAFT = 'draft';
const APPLICATION_STATUS_PENDING_CLERK = 'pending_clerk';
const APPLICATION_STATUS_PENDING_CHAIR = 'pending_chair';
const APPLICATION_STATUS_PENDING_MANAGER = 'pending_manager';
const APPLICATION_STATUS_PENDING_EXECUTIVE = 'pending_executive';
const APPLICATION_STATUS_APPROVED = 'approved';
const APPLICATION_STATUS_REJECTED = 'rejected';

const APPLICATION_STATUS_LABELS = [
    APPLICATION_STATUS_DRAFT => 'Taslak',
    APPLICATION_STATUS_PENDING_CLERK => 'Memur Onayi Bekliyor',
    APPLICATION_STATUS_PENDING_CHAIR => 'Bolum Onayi Bekliyor',
    APPLICATION_STATUS_PENDING_MANAGER => 'Yonetici Onayi Bekliyor',
    APPLICATION_STATUS_PENDING_EXECUTIVE => 'Ust Yonetici Onayi Bekliyor',
    APPLICATION_STATUS_APPROVED => 'Onaylandi',
    APPLICATION_STATUS_REJECTED => 'Reddedildi',
];

const DOCUMENT_REQUIREMENTS = [
    'student_certificate' => 'Ogrenci Belgesi',
    'transcript' => 'Not Belgesi',
    'course_content' => 'Ders Icerikleri',
    'discipline_status' => 'Disiplin Belgesi',
    'osys_result' => 'OSYS Sonuc Belgesi',
    'identity_copy' => 'Nufus Cuzdani Fotokopisi',
    'prep_status' => 'Hazirlik Muafiyet Belgesi',
    'internship_status' => 'Staj Durum Belgesi',
    'top_ten' => '%10 Basari Belgesi',
];

const INSTRUCTION_TYPES = ['1. Ogretim', '2. Ogretim'];
