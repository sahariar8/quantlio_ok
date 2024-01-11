<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\SchedulerJobController;
use Illuminate\Support\Facades\Log;

class queueOrderCodeFinal extends Command
{
    protected $_SchedulerJobController;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'call:queueOrderCodeFinal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule job -> called ';  // getAndStoreQueueOfOrderCodeToProcess

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SchedulerJobController $SchedulerJobController)
    {
        parent::__construct();
        $this->_SchedulerJobController = $SchedulerJobController;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (env('LOG_SCHEDULE_INFO') == 'yes') 
        {
            Log::channel('scheduler_info')->info('-------->> start - queue handler called : ' . now());
        }
        $response = $this->_SchedulerJobController-> getAndStoreRequestedOrderCode();
        if (env('LOG_SCHEDULE_INFO') == 'yes') 
        {
            Log::channel('To insert into Queue - API called successfully.'. $response);
            Log::channel('scheduler_info')->info('------->> end - queue handler called: '. now());
    
            Log::channel('scheduler_info')->info('-------->> start - generatePDFReport handler called : ' . now());
        }
        $response2 = $this->_SchedulerJobController-> generatePDFreportAndUpdateResponse();
        if (env('LOG_SCHEDULE_INFO') == 'yes') 
        {
            Log::channel('pdf generation - API called successfully.'. $response2);
            Log::channel('scheduler_info')->info('------->> end - generatePDFReport handler called: '. now());
        }

        return 0;
    }
}
