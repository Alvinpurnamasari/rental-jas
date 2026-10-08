<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Rental;

function section($t){ echo "\n===== {$t} =====\n"; }

try {
    section("CHECK 1: Rental 13/14/15 current state + rentals table snapshot");
    $ids = [13,14,15];
    foreach ($ids as $id) {
        $r = DB::table('rentals')->where('id',$id)->first();
        if(!$r){ echo "  Rental #{$id}: NOT FOUND\n"; continue; }
        echo "  #{$id}: inv={$r->invoice_number} status={$r->rental_status} pay={$r->payment_status} fine_status={$r->fine_status} fine_amount={$r->fine_amount} fine_paid_amount={$r->fine_paid_amount} total_amount={$r->total_amount} paid_amount={$r->paid_amount} change_amount={$r->change_amount} late_fee={$r->late_fee}\n";
    }
    $allIds = DB::table('rentals')->orderBy('id')->pluck('id')->toArray();
    echo "  ALL rental IDs (count=".count($allIds)."): ".implode(',',$allIds)."\n";
    echo "  range: min=".(count($allIds)?min($allIds):'n/a')." max=".(count($allIds)?max($allIds):'n/a')."\n";
} catch (\Throwable $e) { echo "  ERROR: ".$e->getMessage()."\n"; }

try {
    section("CHECK 2: Related records rental_id=13 (cascade vs orphan)");
    echo "  payments:        ".DB::table('payments')->where('rental_id',13)->count()."\n";
    echo "  rental_items:    ".DB::table('rental_items')->where('rental_id',13)->count()."\n";
    echo "  rental_returns:  ".DB::table('rental_returns')->where('rental_id',13)->count()."\n";
    echo "  guarantees:      ".DB::table('guarantees')->where('rental_id',13)->count()."\n";
    echo "  activity_logs:   ".DB::table('activity_logs')->where('model_type','App\\Models\\Rental')->where('model_id',13)->count()."\n";
    echo "  status_histories:".DB::table('status_histories')->where('model_type','App\\Models\\Rental')->where('model_id',13)->count()."\n";
    echo "  laundries:       ".DB::table('laundries')->where('transaksi_id',13)->count()."\n";
} catch (\Throwable $e) { echo "  ERROR: ".$e->getMessage()."\n"; }

try {
    section("CHECK 3: FK constraints ON DELETE (SHOW CREATE TABLE)");
    foreach (['rentals','rental_items','payments','guarantees','rental_returns','status_histories','activity_logs'] as $t) {
        $rows = DB::select("SHOW CREATE TABLE `$t`");
        $def = $rows[0]->{'Create Table'} ?? '';
        // extract FOREIGN KEY lines
        preg_match_all('/CONSTRAINT `[^`]+` FOREIGN KEY \(`[^`]+`\) REFERENCES `[^`]+` \(`[^`]+`\)(?: ON DELETE [^ ]+)?(?: ON UPDATE [^ ]+)?/', $def, $m);
        echo "  {$t}:\n";
        if (empty($m[0])) { echo "    (no FK)\n"; }
        else { foreach ($m[0] as $fk) echo "    ".$fk."\n"; }
    }
} catch (\Throwable $e) { echo "  ERROR: ".$e->getMessage()."\n"; }

function dryRun($rentalArr,$paymentsArr) {
    $rentalFee=(float)$rentalArr['total_amount'];
    $fineAmount=(float)$rentalArr['fine_amount'];
    $totalPaidRaw=0.0; $refundGiven=0.0;
    foreach ($paymentsArr as $p) {
        if (isset($p['type']) && $p['type']==='refund') $refundGiven+=abs((float)$p['amount']);
        else $totalPaidRaw+=(float)$p['amount'];
    }
    $totalReceived=$totalPaidRaw-$refundGiven;
    $availableForFine=max(0,$totalReceived-$rentalFee);
    $finePaidAmount=min($fineAmount,$availableForFine);
    if ($fineAmount<=0) $fineStatus='none';
    elseif ($finePaidAmount>=$fineAmount) $fineStatus='paid';
    elseif ($finePaidAmount>0) $fineStatus='partial';
    else $fineStatus='unpaid';
    return compact('rentalFee','fineAmount','totalPaidRaw','refundGiven','totalReceived','availableForFine','finePaidAmount','fineStatus');
}

try {
    section("CHECK 4: Dry-run NEW formula (read-only, NO DB WRITE)");
    echo "Formula = RentalService::recalculatePaymentStatus final:\n  totalReceived=SUM(non-refund)-SUM(refund); availableForFine=max(0,totalReceived-rentalFee); finePaidAmount=min(fineAmount,availableForFine)\n\n";

    // 14 & 15 from DB
    foreach ([14,15] as $id) {
        $r=Rental::with('payments')->find($id);
        if(!$r){ echo "  #$id NOT FOUND\n"; continue; }
        $cur=['total_amount'=>$r->total_amount,'fine_amount'=>$r->fine_amount];
        $pArr=$r->payments->map(fn($p)=>['amount'=>$p->amount,'type'=>$p->type])->toArray();
        $res=dryRun($cur,$pArr);
        echo "  Rental #{$id} ({$r->invoice_number}):\n";
        echo "    CURRENT: fine_status={$r->fine_status} fine_paid_amount={$r->fine_paid_amount} fine_amount={$r->fine_amount} paid_amount={$r->paid_amount} change_amount={$r->change_amount}\n";
        echo "    NEW:     fine_status={$res['fineStatus']} fine_paid_amount={$res['finePaidAmount']} | totalReceived={$res['totalReceived']} availableForFine={$res['availableForFine']} (paidRaw={$res['totalPaidRaw']},refund={$res['refundGiven']})\n";
        foreach($r->payments as $p) echo "      pay id={$p->id} type={$p->type} amount={$p->amount}\n";
    }

    // 13: rental IS in DB now (restored), so use DB rental + DB payments directly
    $r13=Rental::with('payments')->find(13);
    if(!$r13){
        echo "  Rental #13 NOT FOUND in DB (use backup)\n";
        $b=json_decode(file_get_contents(__DIR__.'/backup-rental-13.json'),true);
        $bR=$b['rental'];
        $cur=['total_amount'=>$bR['total_amount'],'fine_amount'=>round((float)$bR['fine_amount'])];
        $pArr=array_map(fn($p)=>['amount'=>$p['amount'],'type'=>$p['type']],$b['payments']);
    } else {
        $cur=['total_amount'=>$r13->total_amount,'fine_amount'=>$r13->fine_amount];
        $pArr=$r13->payments->map(fn($p)=>['amount'=>$p->amount,'type'=>$p->type])->toArray();
    }
    $res=dryRun($cur,$pArr);
    echo "  Rental #13:\n";
    if(isset($r13) && $r13){
        echo "    CURRENT(DB): fine_status={$r13->fine_status} fine_paid_amount={$r13->fine_paid_amount} fine_amount={$r13->fine_amount} paid_amount={$r13->paid_amount} change_amount={$r13->change_amount}\n";
    } else {
        echo "    CURRENT(backup): fine_status={$bR['fine_status']} fine_paid_amount={$bR['fine_paid_amount']} fine_amount={$bR['fine_amount']}\n";
    }
    echo "    NEW:     fine_status={$res['fineStatus']} fine_paid_amount={$res['finePaidAmount']} | totalReceived={$res['totalReceived']} availableForFine={$res['availableForFine']} (paidRaw={$res['totalPaidRaw']},refund={$res['refundGiven']})\n";
    foreach($pArr as $p) echo "      pay amount={$p['amount']} type={$p['type']}\n";
} catch (\Throwable $e) { echo "  ERROR: ".$e->getMessage()."\n"; }

try {
    section("CHECK 5: Full audit preview — how many rentals WOULD change (dry-run, no write)");
    $all=Rental::with('payments')->get();
    $chg=[]; $none=0; $paid=0; $partial=0; $unpaid=0; $fineCount=0;
    foreach ($all as $r) {
        $cur=['total_amount'=>$r->total_amount,'fine_amount'=>$r->fine_amount];
        $pArr=$r->payments->map(fn($p)=>['amount'=>$p->amount,'type'=>$p->type])->toArray();
        $res=dryRun($cur,$pArr);
        $fsChanged = $r->fine_status !== $res['fineStatus'];
        $fpaChanged = (float)$r->fine_paid_amount !== (float)$res['finePaidAmount'];
        if ($r->fine_amount>0) $fineCount++;
        if ($fsChanged || $fpaChanged) {
            $chg[]=[$r->id,$r->invoice_number,$r->fine_status,$res['fineStatus'],$r->fine_paid_amount,$res['finePaidAmount'],$r->fine_amount];
        }
    }
    echo "  Total rentals: ".$all->count()."\n";
    echo "  Rentals with fine_amount>0: ".$fineCount."\n";
    echo "  Rentals that WOULD change (fine_status or fine_paid_amount): ".count($chg)."\n";
    foreach ($chg as $c) {
        echo "    #{$c[0]} ({$c[1]}): fine_status {$c[2]}->{$c[3]}, fine_paid_amount {$c[4]}->{$c[5]} (fine_amount={$c[6]})\n";
    }
} catch (\Throwable $e) { echo "  ERROR: ".$e->getMessage()."\n"; }

echo "\n=== DONE (read-only) ===\n";
