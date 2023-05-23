<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicketReplyAttachment extends Model
{
    use SoftDeletes;

    // Relations
    public function relationReply() {
        return $this->belongsTo("App\SupportTicketReply");
    }
    // Helper Functions
    public function getName() {
        if (!empty($this->name)) {
            return $this->name;
        } else {
            return "";
        }
    }
    public function getURL() {
        if (!empty($this->file)) {
            return url("/storage/{$this->file}");
        } else {
            return "";
        }
    }
}
