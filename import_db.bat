@echo off
mysql -u blaqdev -pcodingscience -e "CREATE DATABASE IF NOT EXISTS edu_app_clean;"
mysql -u blaqdev -pcodingscience edu_app_clean < c:\laragon\www\school_app\edu_app_clean.sql
