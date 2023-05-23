<?php

namespace App;

use App\Http\Libraries\HelperLibrary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use SoftDeletes;

    // Relations
    public function relationReplies() {
        return $this->hasMany("App\SupportTicketReply", "support_ticket_id", "id");
    }
    public function relationFirmUser() {
        return $this->belongsTo("App\FirmUser", "firm_user_id", "id");
    }
    // Scopes
    public function scopeNotDeleted($query) {
        return $query->where("deleted_at", null);
    }
    public function scopeFirmUser($query, $id) {
        return $query->where('firm_user_id', $id);
    }
    // Helper Functions
    public function getShortSubject() {
        return strlen($this->subject) > 30 ? substr(strip_tags($this->subject),0,27) . "..." : $this->subject;
    }
    public function getDescription() {
        return $this->getPrimaryReply()->comment;
    }
    public function getPriority() {
        switch ($this->priority) {
            case 1:
                return "High";
            break;
            case 2:
                return "Medium";
            break;
            case 3:
                return "Low";
            break;
            default:
                return "-";
        }
    }
    public function getStatus() {
        switch ($this->status) {
            case 1:
                return "New";
            break;
            case 2:
                return "Acknowledged";
            break;
            case 3:
                return "In-Progress";
            break;
            case 4:
                return "Resolved";
            break;
            case 5:
                return "Overdue";
            break;
            case 6:
                return "Closed";
            break;
            default:
                return "-";
        }
    }
    public function getPrimaryReply() {
        return $this->relationReplies()->first();
    }
    public function getPrimaryAttachment() {
        if ($this->getPrimaryReply()->relationAttachment != null) {
            return $this->getPrimaryReply()->relationAttachment()->first();
        } else {
            return new SupportTicketReplyAttachment();
        }
    }
    public function getReplies() {
        return $this->relationReplies()->skip(1)->limit($this->relationReplies()->count() - 1)->get();
    }
    public function getUser() {
        return $this->relationFirmUser->getName();
    }
    public function getFirmName() {
        if ($this->relationFirmUser->firm) {
            return $this->relationFirmUser->firm->name;
        } else {
            return "-";
        }
    }
    public function getCreatedTime() {
        return $this->created_at->format("m-d-Y (h:i A)");
    }
}
