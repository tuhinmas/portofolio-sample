<?php

if (!function_exists("note_types_to_update_event_history")) {

    function note_types_to_update_event_history($note_type)
    {
        $note_types = [1, 2, 3, 4, 6];
        if (in_array($note_type, $note_types)) {
            return true;
        }
        return false;
    }
}

if (!function_exists("event_notification_text")) {
    function event_notification_text($status)
    {
        $subtitle = null;
        if ($status == "3") {
            $subtitle = "Pengajuan event perlu disetujui";
        } elseif ($status == "14") {
            $subtitle = "Event perlu disetujui manajemen";
        } elseif ($status == "11") {
            $subtitle = "Pengajuan pembatalan event";
        } elseif ($status == "2") {
            $subtitle = "Pengajuan perlu disetujui supervisor";
        }

        return $subtitle;
    }
}

if (!function_exists("event_notification_permission")) {

    function event_notification_permission($user)
    {
        if ($user->hasAnyPermission([
            "(B) Persetujuan Rencana Event",
            "(B) Persetujuan Rencana Event Manajemen",
            "(B) Pembatalan Rencana Event",
            "(F) Konfirmasi Rencana (2)"
        ])) {
            return true;
        }

        return false;
    }
}

if (!function_exists("event_notification_by_permission")) {
    function event_notification_by_permission($user, $permission)
    {
        if ($user->hasAnyPermission($permission)) {
            return true;
        }
        return false;
    }
}
