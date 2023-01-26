<?php

class RT_AccessDataBase
{

    public function getAppVersion()
    {
        global $wpdb;
        $result = $wpdb->get_results("SELECT appversion FROM " . $wpdb->prefix . "rivigo_tracking", OBJECT);
        return $result[0]->appversion;
    }

    public function updateAppVersion($appVersion)
    {
        global $wpdb;
        $wpdb->get_results("UPDATE " . $wpdb->prefix . "rivigo_tracking SET appversion={$appVersion}");
    }
}
