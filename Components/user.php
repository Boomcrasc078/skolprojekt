<?php
session_start();
class User
{
    public $username;

    function __construct($username)
    {
        $this->username = $username;
    }
}
?>