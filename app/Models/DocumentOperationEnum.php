<?php

namespace App\Models;

enum DocumentOperationEnum:string
{
    case Read = "Read";
    case Created = "Created";
    case Modified = "Modified";
    case Deleted = "Deleted";
    case Add_Permission = "Add_Permission";
    case Remove_Permission = "Remove_Permission";
    case Send_Email = "Send_Email";
    case Download = "Download";
}
