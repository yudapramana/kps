<?php

namespace App\Enums;

enum RoleType: int
{
    // =========================
    // SYSTEM LEVEL
    // =========================
    case SUPERADMIN = 1;

    // =========================
    // ADMIN LEVEL
    // =========================
    case MASTER_ADMIN  = 2;
    case FINANCE_ADMIN = 3;
    case CONTENT_ADMIN = 4;
    case SCIENCE_ADMIN = 5;

    // =========================
    // OPERATIONAL
    // =========================
    case COMMITTEE = 6;

    // =========================
    // END USER
    // =========================
    case PARTICIPANT = 7;
}
