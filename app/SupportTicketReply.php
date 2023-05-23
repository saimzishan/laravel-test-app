<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicketReply extends Model
{
    use SoftDeletes;

    // Relations
    public function relationTicket() {
        return $this->belongsTo("App\SupportTicket", "support_ticket_id", "id");
    }
    public function relationAttachment() {
        return $this->hasOne("App\SupportTicketReplyAttachment", "support_ticket_reply_id", "id");
    }
    public function user() {
        return $this->morphTo();
    }
    // Helper Functions
    public function getUser() {
        return $this->user->getName();
    }
    public function getCreatedTime() {
        return $this->created_at->format("m-d-Y (h:i A)");
    }
    public function getAttachment() {
        if ($this->relationAttachment != null) {
            return $this->relationAttachment()->first();
        } else {
            return new SupportTicketReplyAttachment();
        }
    }
}
