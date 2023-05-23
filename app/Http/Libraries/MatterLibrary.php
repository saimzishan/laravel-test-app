<?php

namespace App\Http\Libraries;

use App\CLInvoice;
use App\CLMatter;
use App\CLTask;
use App\PPInvoice;
use App\PPMatter;
use App\PPTask;

class MatterLibrary
{

    protected $firm_id = 0;
    protected $firm_integration = "";
    protected $firm_package = "";

    public function __construct($firm_id=null, $firm_integration=null, $firm_package=null) {
        $this->firm_id = $firm_id != null ? $firm_id : HelperLibrary::getFirmID();
        $this->firm_integration = $firm_integration != null ? $firm_integration : HelperLibrary::getFirmIntegration();
        $this->firm_package = $firm_package != null ? $firm_package : HelperLibrary::getFirmPackage();
    }
    public function packageFoundation($status, $user, $mt) {
        if ($this->firm_integration=="practice_panther") {
            $data = PPMatter::where("firm_id", $this->firm_id)->where("status", "Open")->orderBy("created_at", "desc");
        } else {
            $data = CLMatter::where("firm_id", $this->firm_id)->where("status", "Open")->orderBy("created_at", "desc");
        }
        if ($status == "red") {
            $data = $data->doesntHave('invoices')->doesntHave('timeEntries');
        } else if ($status == "yellow") {
            $data = $data->where(function($q){
                $q->where(function($q1){
                    $q1->has('timeEntries')->doesntHave("invoices");
                });
                $q->orWhere(function($q1){
                    $q1->doesntHave('timeEntries')->has("invoices");
                });
            });
        } else if ($status == "green") {
            $data = $data->has('invoices')->has('timeEntries');
        }
        if ($user != "all") {
            if ($this->firm_integration=="practice_panther") {
                $data = $data->whereHas("users", function ($q) use ($user) {
                    $q->where("pp_users.id", $user);
                });
            } else {
                $data = $data->whereHas("users", function ($q) use ($user) {
                    $q->where("cl_users.id", $user);
                });
            }
        }
        if ($mt != "all") {
            $data = $data->where("matter_type", $mt);
        }
        return $data->get();
    }
    public function packageFoundationPlus($status, $user, $mt) {
        return $this->packageFoundation($status, $user, $mt);
    }
    public function packageEnhanced($status, $user, $mt) {
        if ($this->firm_integration=="practice_panther") {
            $data = PPMatter::where("firm_id", $this->firm_id)->where("status", "Open")->orderBy("created_at", "desc");
        } else {
            $data = CLMatter::where("firm_id", $this->firm_id)->where("status", "Open")->orderBy("created_at", "desc");
        }
        if ($status == "red") {
            $data = $data->doesntHave('invoices')->doesntHave('tasks')->doesntHave('timeEntries');
        } else if ($status == "yellow") {
            $data = $data->where(function($q){
                $q->where(function($q1){
                    $q1->has('tasks')->doesntHave('timeEntries')->doesntHave("invoices");
                });
                $q->orWhere(function($q1){
                    $q1->has('tasks')->has('timeEntries')->doesntHave("invoices");
                });
                $q->orWhere(function($q1){
                    $q1->doesntHave('tasks')->has('timeEntries')->has("invoices");
                });
            });
        } else if ($status == "green") {
            $data = $data->has('invoices')->has('tasks')->has('timeEntries');
        }
        if ($user != "all") {
            if ($this->firm_integration=="practice_panther") {
                $data = $data->whereHas("users", function ($q) use ($user) {
                    $q->where("pp_users.id", $user);
                });
            } else {
                $data = $data->whereHas("users", function ($q) use ($user) {
                    $q->where("cl_users.id", $user);
                });
            }
        }
        if ($mt != "all") {
            $data = $data->where("matter_type", $mt);
        }
        return $data->get();
    }
    public function red($user, $mt, $callback=null)
    {
        if ($this->firm_integration=="practice_panther") {
            $check = PPTask::where("firm_id", $this->firm_id)->count();
        } else {
            $check = CLTask::where("firm_id", $this->firm_id)->count();
        }
        if ($this->firm_package == 'foundation') {
            $data = $this->packageFoundation("red", $user, $mt);
            $data1 = $this->packageFoundation("green", $user, $mt);
        } elseif ($this->firm_package == 'foundation_plus' || $this->firm_package == 'trial') {
            $data = $this->packageFoundationPlus("red", $user, $mt);
            $data1 = $this->packageFoundationPlus("green", $user, $mt);
        } elseif ($this->firm_package == 'enhanced' && $check > 0) {
            $data = $this->packageEnhanced("red", $user, $mt);
            $data1 = $this->packageEnhanced("green", $user, $mt);
        } elseif ($this->firm_package == 'enhanced' && $check == 0) {
            $data = $this->packageFoundation("red", $user, $mt);
            $data1 = $this->packageFoundation("green", $user, $mt);
        }
        foreach($data1 as $d) {
            if ($this->firm_integration=="practice_panther") {
                $invoices = PPInvoice::where('pp_matter_id',$d->id)->where("firm_id", $this->firm_id)->where("invoice_type", "sale")
                    ->where("total_outstanding",">", "0")->get();
            } else {
                $invoices = CLInvoice::whereHas('matter', function($q)use($d){
                    $q->where("cl_matters.id", $d->id);
                })->where("firm_id", $this->firm_id)->whereNotIn("state", ["deleted", "void","draft"])->where("due",">", "0")->get();
            }
            $inv_count_red = 0;
            $inv_count_yellow = 0;
            $inv_count_green = 0;
            foreach ($invoices as $inv) {
                $cd = new \DateTime();
                $id = new \DateTime(substr($inv->created_at, 0, 10));
                $diff = $id->diff($cd)->format("%a");
                if ($diff > 0 && $diff <= 30) {
                    $inv_count_green++;
                } elseif ($diff > 30 && $diff <= 60) {
                    $inv_count_yellow++;
                } elseif ($diff > 60) {
                    $inv_count_red++;
                }
            }
            if ($this->firm_package == 'enhanced' && $check > 0) {
                $tsk_count_red = 0;
                $tsk_count_yellow = 0;
                $tsk_count_green = 0;
                if ($this->firm_integration=="practice_panther") {
                    $tasks = PPTask::where('pp_matter_id', $d->id)->where("firm_id", $this->firm_id)->where("status", "NotCompleted")->get();
                } else {
                    $tasks = CLTask::where('clio_matter_id', $d->id)->where("firm_id", $this->firm_id)->where("status", "<>", "complete")->get();
                }
                foreach ($tasks as $tsk) {
                    $cd = new \DateTime();
                    $id = new \DateTime(substr($tsk->due_date, 0, 10));
                    $diff = $id->diff($cd)->format("%a");
                    if ($diff == 0) {
                        $tsk_count_green++;
                    } elseif ($diff > 0 && $diff <= 3) {
                        $tsk_count_yellow++;
                    } elseif ($diff > 3) {
                        $tsk_count_red++;
                    }
                }
                if (($inv_count_red > 0 && $inv_count_yellow >= 0 && $inv_count_green >= 0) || ($tsk_count_red > 0 && $tsk_count_yellow >= 0 && $tsk_count_green >= 0)) {
                    $data->add($d);
                }
            } else {
                if ($inv_count_red > 0 && $inv_count_yellow >= 0 && $inv_count_green >= 0) {
                    $data->add($d);
                }
            }
        }
        if ($callback!=null && is_callable($callback)) {
            foreach ($data as $d) {
                $callback($d);
            }
        }
        return $data;
    }
    public function yellow($user, $mt, $callback=null)
    {
        if ($this->firm_integration=="practice_panther") {
            $check = PPTask::where("firm_id", $this->firm_id)->count();
        } else {
            $check = CLTask::where("firm_id", $this->firm_id)->count();
        }
        if ($this->firm_package == 'foundation') {
            $data = $this->packageFoundation("yellow", $user, $mt);
            $data1 = $this->packageFoundation("green", $user, $mt);
        } elseif ($this->firm_package == 'foundation_plus' || $this->firm_package == 'trial') {
            $data = $this->packageFoundationPlus("yellow", $user, $mt);
            $data1 = $this->packageFoundationPlus("green", $user, $mt);
        } elseif ($this->firm_package == 'enhanced' && $check > 0) {
            $data = $this->packageEnhanced("yellow", $user, $mt);
            $data1 = $this->packageEnhanced("green", $user, $mt);
        } elseif ($this->firm_package == 'enhanced' && $check == 0) {
            $data = $this->packageFoundation("yellow", $user, $mt);
            $data1 = $this->packageFoundation("green", $user, $mt);
        }
        foreach ($data1 as $d) {
            if ($this->firm_integration=="practice_panther") {
                $invoices = PPInvoice::where('pp_matter_id',$d->id)->where("firm_id", $this->firm_id)->where("invoice_type", "sale")
                    ->where("total_outstanding",">", "0")->get();
            } else {
                $invoices = CLInvoice::whereHas('matter', function($q)use($d){
                    $q->where("cl_matters.id", $d->id);
                })->where("firm_id", $this->firm_id)->whereNotIn("state", ["deleted", "void","draft"])->where("due",">", "0")->get();
            }
            $inv_count_red = 0;
            $inv_count_yellow = 0;
            $inv_count_green = 0;
            foreach ($invoices as $inv) {
                $cd = new \DateTime();
                $id = new \DateTime(substr($inv->created_at, 0, 10));
                $diff = $id->diff($cd)->format("%a");
                if ($diff > 0 && $diff <= 30) {
                    $inv_count_green++;
                } elseif ($diff > 30 && $diff <= 60) {
                    $inv_count_yellow++;
                } elseif ($diff > 60) {
                    $inv_count_red++;
                }
            }
            $invoices = $inv_count_yellow > 0 && $inv_count_green >= 0 && $inv_count_red == 0;
            if ($this->firm_package == 'enhanced' && $check > 0) {
                $tsk_count_red = 0;
                $tsk_count_yellow = 0;
                $tsk_count_green = 0;
                if ($this->firm_integration=="practice_panther") {
                    $tasks = PPTask::where('pp_matter_id', $d->id)->where("firm_id", $this->firm_id)->where("status", "NotCompleted")->get();
                } else {
                    $tasks = CLTask::where('clio_matter_id', $d->id)->where("firm_id", $this->firm_id)->where("status", "<>", "complete")->get();
                }
                foreach($tasks as $tsk) {
                    $cd = new \DateTime();
                    $id = new \DateTime(substr($tsk->due_date, 0, 10));
                    $diff = $id->diff($cd)->format("%a");
                    if ($diff == 0) {
                        $tsk_count_green++;
                    } elseif ($diff > 0 && $diff <= 3) {
                        $tsk_count_yellow++;
                    } elseif ($diff > 3) {
                        $tsk_count_red++;
                    }
                }
                $tasks = $tsk_count_yellow > 0 && $tsk_count_red == 0 && $tsk_count_green >= 0;
                if (($tasks == true && $inv_count_red == 0) || ($tsk_count_red == 0 && $invoices == true)) {
                    $data->add($d);
                }
            } else {
                if ($inv_count_yellow > 0 && $inv_count_green >= 0 && $inv_count_red == 0) {
                    $data->add($d);
                }
            }
        }
        if ($callback!=null && is_callable($callback)) {
            foreach ($data as $d) {
                $callback($d);
            }
        }
        return $data;
    }
    public function green($user, $mt, $callback=null)
    {
        if ($this->firm_integration=="practice_panther") {
            $check = PPTask::where("firm_id", $this->firm_id)->count();
        } else {
            $check = CLTask::where("firm_id", $this->firm_id)->count();
        }
        if ($this->firm_package == 'foundation') {
            $data1 = $this->packageFoundation("green", $user, $mt);
            $data = $data1;
        } elseif ($this->firm_package == 'foundation_plus' || $this->firm_package == 'trial') {
            $data1 = $this->packageFoundationPlus("green", $user, $mt);
            $data = $data1;
        } elseif ($this->firm_package == 'enhanced' && $check > 0) {
             $data1 = $this->packageEnhanced("green", $user, $mt);
            $data = $data1;
        } elseif ($this->firm_package == 'enhanced' && $check == 0) {
             $data1 = $this->packageFoundation("green", $user, $mt);
            $data = $data1;
        }
        foreach ($data1 as $d) {
            if ($this->firm_integration=="practice_panther") {
                $invoices = PPInvoice::where('pp_matter_id',$d->id)->where("firm_id", $this->firm_id)->where("invoice_type", "sale")
                    ->where("total_outstanding",">", "0")->get();
            } else {
                $invoices = CLInvoice::whereHas('matter', function($q)use($d){
                    $q->where("cl_matters.id", $d->id);
                })->where("firm_id", $this->firm_id)->whereNotIn("state", ["deleted", "void","draft"])->where("due",">", "0")->get();
            }
            $inv_count = 0;
            foreach ($invoices as $inv) {
                $cd = new \DateTime();
                $id = new \DateTime(substr($inv->created_at, 0, 10));
                $diff = $id->diff($cd)->format("%a");
                if ($diff > 30) {
                    $inv_count++;
                }
            }
            if ($this->firm_package == 'enhanced' && $check > 0) {
                $tsk_count = 0;
                if ($this->firm_integration=="practice_panther") {
                    $column = "due_date";
                    $tasks = PPTask::where('pp_matter_id', $d->id)->where("firm_id", $this->firm_id)->where("status", "NotCompleted")->get();
                } else {
                    $column = "due_at";
                    $tasks = CLTask::where('clio_matter_id', $d->id)->where("firm_id", $this->firm_id)->where("status", "<>", "complete")->get();
                }
                foreach ($tasks as $tsk) {
                    $cd = new \DateTime();
                    $id = new \DateTime(substr($tsk->$column, 0, 10));
                    $diff = $id->diff($cd)->format("%a");
                    if ($diff != 0) {
                        $tsk_count++;
                    }
                }
                if ($inv_count > 0 || $tsk_count > 0) {
                    $key = $data->search($d);
                    $data->pull($key);
                }
            } else {
                if ($inv_count > 0) {
                    $key = $data->search($d);
                    $data->pull($key);
                }
            }
        }
        if ($callback!=null && is_callable($callback)) {
            foreach ($data as $d) {
                $callback($d);
            }
        }
        return $data;
    }

}