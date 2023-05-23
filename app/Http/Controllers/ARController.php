<?php

namespace App\Http\Controllers;

use App\CLContact;
use App\CLInvoice;
use App\Definition;
use App\Exports\ARCurrentExport;
use App\Http\Libraries\ARLibrary;
use App\Http\Libraries\HelperLibrary;
use App\Http\Resources\ARResource;
use App\Http\Resources\ARSummaryResource;
use App\PPAccount;
use App\PPInvoice;
use App\SummaryAllTime;
use App\SummaryAR;
use Illuminate\Http\Request;
use Excel;

class ARController extends Controller
{
    public function all(Request $request) {
        $data = SummaryAR::where("firm_id", HelperLibrary::getFirmID())
            ->select(["contact_id", "contact_name", "total", "outstanding", "percentage_to_sale", "percentage_to_outstanding"])
            ->where("type", "all");
        if ($request->filled("q")) {
            $data->where("contact_name", "like", "%{$request->get("q")}%");
        }
        if ($request->filled('sort-by') && $request->get("sort-by-type") != "-") {
            $data->orderBy($request->get('sort-by'), $request->get('sort-by-type'));
        }
        HelperLibrary::logActivity('User Viewed AR Manager');
        return ARSummaryResource::collection($data->paginate(HelperLibrary::perPage()))->additional([
            "meta" => [
                "query" => $request->filled("q") ? $request->get("q") : ""
            ]
        ]);

    }
    public function current(Request $request) {
        $data = SummaryAR::where("firm_id", HelperLibrary::getFirmID())
            ->select(["contact_id", "contact_name", "total", "outstanding", "percentage_to_sale", "percentage_to_outstanding"])
            ->where("type", "current");
        if ($request->filled('sort-by') && $request->get("sort-by-type") != "-") {
            $data->orderBy($request->get('sort-by'), $request->get('sort-by-type'));
        }
        HelperLibrary::logActivity('User Viewed AR Current');
        return ARSummaryResource::collection($data->paginate(HelperLibrary::perPage()));

    }
    public function late(Request $request) {
        $data = SummaryAR::where("firm_id", HelperLibrary::getFirmID())
            ->select(["contact_id", "contact_name", "total", "outstanding", "percentage_to_sale", "percentage_to_outstanding"])
            ->where("type", "late");
        if ($request->filled('sort-by') && $request->get("sort-by-type") != "-") {
            $data->orderBy($request->get('sort-by'), $request->get('sort-by-type'));
        }
        HelperLibrary::logActivity('User Viewed AR Late');
        return ARSummaryResource::collection($data->paginate(HelperLibrary::perPage()));

    }
    public function delinquent(Request $request) {
        $data = SummaryAR::where("firm_id", HelperLibrary::getFirmID())
            ->select(["contact_id", "contact_name", "total", "outstanding", "percentage_to_sale", "percentage_to_outstanding"])
            ->where("type", "Delinquent");
        if ($request->filled('sort-by') && $request->get("sort-by-type") != "-") {
            $data->orderBy($request->get('sort-by'), $request->get('sort-by-type'));
        }
        HelperLibrary::logActivity('User Viewed AR Delinquent');
        return ARSummaryResource::collection($data->paginate(HelperLibrary::perPage()));
    }
    public function collection(Request $request) {
        $data = SummaryAR::where("firm_id", HelperLibrary::getFirmID())
            ->select(["contact_id", "contact_name", "total", "outstanding", "percentage_to_sale", "percentage_to_outstanding"])
            ->where("type", "Collection");
        if ($request->filled('sort-by') && $request->get("sort-by-type") != "-") {
            $data->orderBy($request->get('sort-by'), $request->get('sort-by-type'));
        }
        HelperLibrary::logActivity('User Viewed AR Collection');
        return ARSummaryResource::collection($data->paginate(HelperLibrary::perPage()));
    }
    /**SUm
     *  Export Manager data of AR section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportManager(Request $request) {
        HelperLibrary::logActivity('User Downloaded AR Manager Data');
        $data = SummaryAR::where("firm_id", HelperLibrary::getFirmID())
            ->select(["contact_id", "contact_name", "total", "outstanding", "percentage_to_sale", "percentage_to_outstanding"])
            ->where("type", "all")->get();
        $data = $data->map(function ($item, $key)  {
            return collect([
                $item->contact_name,
                "$".number_format($item->total,0),
                "$".number_format($item->outstanding,0),
                round($item->percentage_to_sale, 2)."%",
                round($item->percentage_to_outstanding, 2)."%",
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new ARCurrentExport($data), time()."-ar-manager.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new ARCurrentExport($data), time()."-ar-manager.pdf");
        }
    }
    /**
     *  Export Current data of AR section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportCurrent(Request $request) {
        HelperLibrary::logActivity('User Downloaded AR Current Data');
        $data = SummaryAR::where("firm_id", HelperLibrary::getFirmID())
            ->select(["contact_id", "contact_name", "total", "outstanding", "percentage_to_sale", "percentage_to_outstanding"])
            ->where("type", "current")->get();
        $data = $data->map(function ($item, $key)  {
            return collect([
                $item->contact_name,
                "$".number_format($item->total,0),
                "$".number_format($item->outstanding,0),
                round($item->percentage_to_sale, 2)."%",
                round($item->percentage_to_outstanding, 2)."%",
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new ARCurrentExport($data), time()."-ar-current.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new ARCurrentExport($data), time()."-ar-current.pdf");
        }
    }
    /**
     *  Export Late data of AR section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportLate(Request $request) {
        HelperLibrary::logActivity('User Downloaded AR Late Data');
        $data = SummaryAR::where("firm_id", HelperLibrary::getFirmID())
            ->select(["contact_id", "contact_name", "total", "outstanding", "percentage_to_sale", "percentage_to_outstanding"])
            ->where("type", "late")->get();
        $data = $data->map(function ($item, $key)  {
            return collect([
                $item->contact_name,
                "$".number_format($item->total,0),
                "$".number_format($item->outstanding,0),
                round($item->percentage_to_sale, 2)."%",
                round($item->percentage_to_outstanding, 2)."%",
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new ARCurrentExport($data), time()."-ar-late.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new ARCurrentExport($data), time()."-ar-late.pdf");
        }
    }
    /**
     *  Export Delinquent data of AR section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportDelinquent(Request $request) {
        HelperLibrary::logActivity('User Downloaded AR Delinquent Data');
        $data = SummaryAR::where("firm_id", HelperLibrary::getFirmID())
            ->select(["contact_id", "contact_name", "total", "outstanding", "percentage_to_sale", "percentage_to_outstanding"])
            ->where("type", "delinquent")->get();
        $data = $data->map(function ($item, $key)  {
            return collect([
                $item->contact_name,
                "$".number_format($item->total,0),
                "$".number_format($item->outstanding,0),
                round($item->percentage_to_sale, 2)."%",
                round($item->percentage_to_outstanding, 2)."%",
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new ARCurrentExport($data), time()."-ar-delinquent.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new ARCurrentExport($data), time()."-ar-delinquent.pdf");
        }
    }
    /**
     *  Export Collection data of AR section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportCollection(Request $request) {
        HelperLibrary::logActivity('User Downloaded AR Collection Data');
        $data = SummaryAR::where("firm_id", HelperLibrary::getFirmID())
            ->select(["contact_id", "contact_name", "total", "outstanding", "percentage_to_sale", "percentage_to_outstanding"])
            ->where("type", "Collection")->get();
        $data = $data->map(function ($item, $key)  {
            return collect([
                $item->contact_name,
                "$".number_format($item->total,0),
                "$".number_format($item->outstanding,0),
                round($item->percentage_to_sale, 2)."%",
                round($item->percentage_to_outstanding, 2)."%",
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new ARCurrentExport($data), time()."-ar-collection.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new ARCurrentExport($data), time()."-ar-collection.pdf");
        }
    }
}
