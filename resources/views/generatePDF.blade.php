<!DOCTYPE html>
<html>
   <head>
      <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="css/careview-print.css" type="text/css">
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
   </head>
   <body style="margin:0px; padding:0px; background: url(./images/newstar-watermark.png); background-size:contain; background-repeat:no-repeat;">
   
   <script type="text/php">
    if (isset($pdf)) {
      $text = "page {PAGE_NUM} / {PAGE_COUNT}";
      $size = 16;
      $font = $fontMetrics->getFont("Verdana");
      $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
      $x = ($pdf->get_width() - $width) / 2;
      $y = $pdf->get_height() - 35;
      $a = $pdf->get_height() - 60;
      $pdf->page_text($x, $y, $text, $font, $size);
      $textPatient = "Patient Name: {{ $patientName }}";
      $textLocation = "Lab: {{ $in_house_lab_location }}";
      $textDate = "Generated: {{ $reported }}";
      $textAccession = "Accession: {{ $accession }}";
      $pdf->page_text(30, $a , $textPatient, $font, $size);
      $pdf->page_text(30, $y , $textLocation, $font, $size);
      $pdf->page_text(1490, $a , $textAccession, $font, $size);
      $pdf->page_text(1490, $y, $textDate, $font, $size);
   }
   </script>
   <style>
      @print {
         .border-radius{
            border-radius:10px !important;
         }
      }
      @page { margin: 490px 50px 115px 50px; }
      header { position: fixed; left: 0px; top: -490px; right: 0px; height: 150px; text-align: center; }
      footer{  position: absolute; height: 20px;bottom:85px;line-height: 1.3;font-family: 'Open Sans', sans-serif; font-size:16px;}
   </style>
   <header>
      <table cellpadding="0" cellspacing="0" Border="0" style="max-width:1320px; width:100%; margin:0 auto;">
         <tr>
            <td>
               <table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                  <tr>
                     <td style="width:25%"><img style="width:163px" src="images/qr.png" class="qr-image"></td>
                     <td style="width:40%; text-align:center"><img style="width:450px" src="images/careview360.png" class="logo-image"></td>
                     <td style="width:35%; text-align:right">
                        <img style="width: 250px;" src="images/newstar.png" class="new-star-print-logo">
                        <p style="margin:0; color:#084ba0; margin-top:5px;font-family: 'Open Sans', sans-serif;text-align:right; font-size:16px" class="print-fs-14">{{ $in_house_lab_location }}</p>
                        @if($labLocation->location == $in_house_lab_location || $labLocationTempe->location == $in_house_lab_location)
                        <p style="margin:0; color:#084ba0; margin-top:5px;font-family: 'Open Sans', sans-serif;text-align:right; font-size:16px" class="print-fs-14">{{ $labLocation->address }}</p>
                        <p style="margin:0; color:#084ba0; margin-top:5px;font-family: 'Open Sans', sans-serif;text-align:right; font-size:16px" class="print-fs-14">Phone: {{ $labLocation->phone }} | Fax:{{ $labLocation->fax }}</p>
                        <p style="margin:0; color:#084ba0; margin-top:5px;font-family: 'Open Sans', sans-serif;text-align:right; font-size:16px" class="print-fs-14">CLIA ID: {{ $labLocation->CLIA }}</p>
                        <p style="margin:0; color:#084ba0; margin-top:5px;font-family: 'Open Sans', sans-serif;text-align:right; font-size:16px" class="print-fs-14">Laboratory Director : {{ $labLocation->director }}</p>
                        @endif
                     </td>
                  </tr>
               </table>
            </td>
         </tr>
         <tr>
            <td>
               <table cellpadding="0" cellspacing="0" border="0" style="width:100%; margin-top:40px; margin-bottom:22px" class="display-block">
                  <tr>
                     <td style="20%; vertical-align:top;">
                        <strong style="font-weight:800;font-family: 'Open Sans', sans-serif; font-size:22px;color:#084ba0;" class="fs-16">PATIENT</strong>
                        <p style="margin:0;font-family: 'Open Sans', sans-serif; font-size:22px;color:#084ba0;" class="fs-16">DEMOGRAPHICS</p>
                     </td>
                     <td style="width:26.6666667%; padding:0px 10px;vertical-align:top;">
                        <table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                           <tr>
                              <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">Patient Name: {{ $patientName }}</td>
                           </tr>
                           <tr>
                              <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">DOB: {{ $patientDOB }}</td>
                           </tr>
                           <tr>
                              <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">Patient Gender: {{ $patientGender }}</td>
                           </tr>
                           <tr>
                              @if(!empty($phone))
                                 <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">Patient Phone Number: {{ $phone }}</td>
                              @else
                              <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">Patient Phone Number: N/A </td>
                              @endif   
                           </tr>
                        </table>
                     </td>
                     <td style="width:26.6666667%; padding:0px 10px;vertical-align:top;">
                        <table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                           <tr>
                              <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">Account: {{ $account }}</td>
                           </tr>
                           <tr>
                              <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">Provider: {{ $provider }}</td>
                           </tr>
                           <tr>
                              <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">Accession: {{ $accession }}</td>
                           </tr>
                           <tr>
                           @foreach($orders as $updated)
                           @if($updated->order_code == $code)
                              @if($updated->report_status == '1' )
                              <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">Report Status: FINAL</td>
                              @else
                              <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">Report Status: AMENDED</td>
                              @endif 
                           @endif 
                           @endforeach                           
                           </tr>
                        </table>
                     </td>
                     <td style="width:26.6666667%; padding:0px 10px;vertical-align:top;">
                        <table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                           <tr>
                              <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">Order Code: {{ $code }} ({{ $sample_type }}) </td>
                           </tr>
                           <tr>
                              <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">Collected: {{ $collected }}</td>
                           </tr>
                           <tr>
                              <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">Received: {{ $receivedDate }} </td>
                           </tr>
                           <tr>
                              <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">Reported: {{ $reported }}</td>
                           </tr>
                        </table>
                     </td>
                  </tr>
               </table>
            </td>
         </tr>
         <tr>
            <td style="height:2px; background:#084ba0; margin-top:20px;"></td>
         </tr>
      </table>
   </header>
   <table cellpadding="0" cellspacing="0" Border="0" style="max-width:1320px; width:100%;margin:0 auto;">
   <tr>
      <td>
         <table cellpadding="0" cellspacing="0" border="0" style="width:100%; margin-top:20px; margin-bottom:20px" class="display-block">
            <tr>
               <td style="20%; vertical-align:top;">
                  <strong style="font-weight:800;font-family: 'Open Sans', sans-serif; font-size:22px;color:#084ba0;" class="fs-16">PRESCRIPTION</strong>
                  <p style="margin:0;font-family: 'Open Sans', sans-serif; font-size:22px;color:#084ba0;" class="fs-16">LIST</p>
               </td>
               <td style="width:40%; padding:0px 10px;vertical-align:top;">
                  <table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                     <tr>
                        @if(!empty($medications))
                        <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px;font-weight:bold" class="print-fs-14">{{ $medications }} </td>
                        @else
                        <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px;font-weight:bold" class="print-fs-14">None </td>
                        @endif 
                     </tr>
                  </table>
               </td>
               
               
               <td style="20%; vertical-align:top;">
                  <strong style="font-weight:800;font-family: 'Open Sans', sans-serif; font-size:22px;color:#084ba0;" class="fs-16">DIAGNOSIS</strong>
                  <p style="margin:0;font-family: 'Open Sans', sans-serif; font-size:22px;color:#084ba0;" class="fs-16">CODES</p>
               </td>
               <td style="width:40%; padding:0px 10px;vertical-align:middle;">
                  <table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                     <tr>
                        @if(!empty($icdCode))
                        <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">{{ $icdCode }}</td>
                        @else
                        <td style="width:100%; border-bottom:2px solid #dedede;font-family: 'Open Sans', sans-serif;font-size:22px;padding:5px 0px; font-weight:bold" class="print-fs-14">{{ $icdCode }}</td>
                        @endif 
                     </tr>
                  </table>
               </td>
               
            </tr>
         </table>
      </td>
   </tr>
   </table>
   <table cellpadding="0" cellspacing="0" Border="0" style="max-width:1320px; width:100%;margin:40px auto;">
      <tr>
         <td>
            <table cellpadding="12" cellspacing="12" border="0" style="width:100%; border-spacing: 15px 0;" class="display-block">
               <tr>
                  <td class="border-radius" style="width:100%; padding:0px !important;border:2px solid #ff1616; border-radius:10px !important; vertical-align:top; corner-radius:10px;">
                     <table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                        <tr style="height:30px;">
                           <td colspan="2" style="vertical-align:top; text-align:center; font-family: 'Open Sans', sans-serif; font-size:18px; font-weight:bold; height:28px">INCONSISTENT</td>
                        </tr>
                        <tr style="background:#ff1616">
                           <td style="vertical-align:middle;height:40px;font-family: 'Open Sans', sans-serif;font-size: 24px;color:#fff;padding-left: 10px;font-weight: bold;" class="fs-18">NOT PRESCRIBED</td>
                           <td style="vertical-align:middle;height:40px;font-family: 'Open Sans', sans-serif;font-size: 24px;color:#fff;padding-right: 10px;font-weight: 400; text-align:right" class="fs-18">DETECTED</td>
                       </tr>
                        <tr>
                           <td colspan="2">
                              <table cellpadding="0" cellspacing="0" border="0" style="width:100%;padding:0px 15px;" class="print-height">
                                 
                              @if(!empty($notPrescribedDetected))
                                 <tr>
                                    <td style="width:25%; vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-left: 10px;font-weight: 500;" class="print-fs-14">Class</td>
                                    <td style="width:25%;vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-right: 10px;font-weight: 500;" class="print-fs-14">Test</td>
                                    <td style="width:25%;vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-right: 10px;font-weight: 500;text-align: right;" class="print-fs-14">Cutoff <sup>(ng/mL) </sup></td>
                                    <td style="width:25%;vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-left: 10px;font-weight: 500;text-align: right; white-space:nowrap" class="print-fs-14">Results <sup>(ng/mL) </sup></td>
                                 </tr>
                              @foreach($notPrescribedDetected as $key => $testType)
                                 <tr class="table-6-row">
                                    <td>
                                       @foreach($tests as $resultValue)
                                          @if($key == $resultValue->dendi_test_name)
                                          <p style="font-family: 'Open Sans';text-transform:uppercase;font-size:21px;margin: 0px !important;">{{  $resultValue->class }}</p>
                                          @endif 
                                       @endforeach
                                    </td>
                                    <td>
                                    @foreach($tests as $resultValue)
                                       @if($key == $resultValue->dendi_test_name)
                                          @if(empty($resultValue->description))
                                          <p style="font-weight:600;font-family: 'Open Sans';font-size:21px;margin: 0px !important;">{{  $key }}</p> 
                                          @else
                                          <p style="font-weight:600;font-family: 'Open Sans';font-size:21px;margin: 0px !important;">{{  $key }} ({{ $resultValue->description }})</p> 
                                          @endif
                                       @endif  
                                    @endforeach
                                    </td>
                                    
                                    <td>
                                    @foreach($tests as $resultValue)
                                       @if($key == $resultValue->dendi_test_name)
                                       <p style="font-family: 'Open Sans';text-align: right;font-size:21px;margin: 0px !important;">{{  $resultValue->LLOQ }}</p>
                                       @endif 
                                    @endforeach
                                    </td> 
                                    <td>
                                    @foreach($tests as $resultValue)
                                       @if($key == $resultValue->dendi_test_name)
                                          @if($testType['result_quantitative'] > $resultValue->ULOQ)
                                             <p style="font-family: 'Open Sans';text-align: right;font-size:21px;margin: 0px !important;">{{  " > ". $resultValue->ULOQ  }}</p>
                                          @else
                                             <p style="font-family: 'Open Sans';text-align: right;font-size:21px;margin: 0px !important;">{{  number_format((float)$testType['result_quantitative'], 2, '.', '') }}</p>
                                          @endif 
                                       @endif 
                                    @endforeach
                                    </td>
                                 </tr>
                              <tr>
                              <td colspan="4">
                              @foreach($tests as $resultValue)
                                 @if(ucwords($key) == ucwords($resultValue->dendi_test_name)|| $key == $resultValue->dendi_test_name)
                                    @if($resultValue->class == 'NSAID' && strpos($icdCode, 'Chronic kidney disease') !== false)
                                    <p style="color:red;font-family: 'Open Sans';margin: 0px !important;font-size:21px">[CI] NSAID medications reduce blood flow to the kidneys and should be avoided for patients with Chronic Kidney Disease</p>
                                    @endif 
                                 @endif 
                              @endforeach
                              @foreach($arrayResult as $name => $value)
                                 @if(ucwords($name) == ucwords($key))
                                    @if(str_contains($value, '[CI]') && ucfirst($name) == "Metformin" && str_contains($value, 'Chronic kidney disease'))
                                       <p style="color:red;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{  $value }} ( {{ $metforminText }} ) </p>
                                    @elseif(str_contains($value, '[CI]'))
                                       @foreach($tests as $resultValue)
                                          @if($key == ucwords($resultValue->dendi_test_name) || $key == $resultValue->dendi_test_name)
                                             @if($resultValue->class == "Diuretic" || $resultValue->class == "Antidiabetic" && str_contains($value, 'Chronic kidney disease'))
                                                <p style="color:red;font-family: 'Open Sans';margin: 0px !important;font-size:21px;display:none !important;"></p>
                                             @else
                                             <p style="color:red;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{  $value }}</p>  
                                             @endif
                                          @endif
                                       @endforeach  
                                    @elseif(str_contains($value, 'Salicylic acid') && str_contains($value, 'Acetylsalicylic acid'))
                                       <p style="color:#FF4500;display:none !important;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{  $value }}</p>
                                    @else 
                                    <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{  $value }}</p>
                                 @endif
                                 @endif 
                              @endforeach
                              @if(!array_key_exists($key,$contraindicationComments))
                                 @if(is_array($testType))
                                    @if(strtoupper($testType['class']) == 'NSAID' && !empty($anticoagulantClassTestArray))
                                       <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">[DDI] (Bleeding Risk) {{ implode(",",$anticoagulantClassTestArray['ANTICOAGULANT']) }} </p>   
                                    @endif

                                    @if(strtoupper($testType['class']) == 'ANTICOAGULANT' && !empty($nsaidClassTestArray))
                                       <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">[DDI] (Bleeding Risk) {{ implode(",",$nsaidClassTestArray['NSAID']) }}</p>   
                                    @endif
                                 @endif
                              @endif 
                              @foreach($contraindicationComments as $keys => $description)
                                 @if(ucwords($keys) == ucwords($key))
                                    @if(is_array($testType) && (strtoupper($testType['class']) == 'NSAID' || strtoupper($testType['class']) == 'ANTICOAGULANT'))
                                       @if(strtoupper($testType['class']) == 'NSAID' && !empty($anticoagulantClassTestArray))
                                          <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{ $description }} - (Bleeding Risk) {{ implode(",",$anticoagulantClassTestArray['ANTICOAGULANT']) }}</p>   
                                       @endif

                                       @if(strtoupper($testType['class']) == 'ANTICOAGULANT' && !empty($nsaidClassTestArray))
                                          <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{ $description }} - (Bleeding Risk) {{ implode(",",$nsaidClassTestArray['NSAID']) }}</p>   
                                       @endif
                                    @else
                                       <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{  $description }}</p>
                                    @endif  
                                 @endif 
                                 @if(ucwords($key) == 'Aspirin' || ucwords($key) == 'Acetylsalicylic Acid')
                                    <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">[DDI] Many OTC medications, skincare products, food, and beverage products contain elevated levels of Salicylates</p>
                                 @endif 
                              @endforeach
                              @if(ucwords($key) == 'Naltrexone')
                                 <p style="color:#111;font-family: 'Open Sans';margin: 0px !important;font-size:21px">FDA designated for treatment of CRPS</p>
                              @endif 
                              </td>
                              </tr>
                              @if($key == 'Methamphetamine')
                                 @foreach($methData as $methKey => $methValue)
                                 <tr class="table-6-row">
                                    <td>
                                       &nbsp;
                                    </td>
                                    <td>
                                       <p style="font-weight:600;font-family: 'Open Sans';font-size:21px">{{  $methKey }}</p>
                                    </td>
                                    <td>
                                    &nbsp;
                                    </td> 
                                    <td>
                                    <p style="font-family: 'Open Sans';text-align: right;font-size:21px">{{  floor($methValue['result_quantitative'])." % "  }}</p>
                                    </td>
                                 </tr>
                                 @if($methKey == 'D-Methamphetamine %')   
                                    @if($methValue['result_quantitative'] != '0')
                                       <tr class="table-6-row">
                                          <td>
                                          &nbsp;
                                          </td>
                                          <td colspan="3">
                                             <p style="font-family: 'Open Sans';margin: 0px !important;font-size:21px;color:red;">Potential Illicit Methamphetamine Identified</p>
                                          </td>
                                       </tr> 
                                    @else
                                    <tr class="table-6-row">
                                       <td>
                                       &nbsp;
                                       </td>
                                       <td colspan="3">
                                       <p style="font-family: 'Open Sans';margin: 0px !important;font-size:21px;">OTC Nasal Decongestant</p>
                                       </td>
                                    </tr> 
                                    @endif 
                                 @endif 
                                 @endforeach
                              @endif 
                              @endforeach
                              @else
                                 <td colspan="4" style="text-align:center;">
                                    <p style="font-family: 'Open Sans';text-transform:uppercase;font-weight:500;font-size:21px">"No Results"</p>
                                 </td>
                                 </tr>
                              @endif 
                                 
                              </table>
                           </td>
                        </tr>
                     </table>
                  </td>
               </tr>
            </table>
         </td>
      </tr>
   </table>
   
   <table cellpadding="0" cellspacing="0" Border="0" style="max-width:1320px; width:100%;margin:40px auto;vertical-align:top;">
      <tr>
         <td>
            <table cellpadding="12" cellspacing="12" border="0" style="width:100%;border-spacing: 15px 0;" class="display-block">
            
               <tr>
               <td style="width: 70%;border:2px solid #ff1616;Border-radius: 10px; corner-radius: 10px;padding: 0;vertical-align:top;">
                        <table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                        <tr style="height:30px;">
                           <td colspan="2" style="vertical-align:top; text-align:center; padding:10px 0px;font-family: 'Open Sans', sans-serif; font-size:18px; font-weight:bold; height:30px">INCONSISTENT</td>
                        </tr>
                        <tr style="background:#ff1616">
                           <td style="vertical-align:middle;height:40px;font-family: 'Open Sans', sans-serif;font-size: 24px;color:#fff;padding-left: 10px;font-weight: bold;" class="fs-18">PRESCRIBED</td>
                           <td style="vertical-align:middle;height:40px;font-family: 'Open Sans', sans-serif;font-size: 24px;color:#fff;padding-right: 10px;font-weight: 400; text-align:right" class="fs-18">NOT DETECTED</td>
                        </tr>
                        <tr>
                           <td colspan="2">
                              <table cellpadding="0" cellspacing="0" border="0" style="width:100%;padding:0px 20px;" class="print-height">
                                 
                                 @if(!empty($prescribedNotDetected))
                                 <tr>
                                    <td style="width:25%; vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-left: 10px;font-weight: 500;" class="print-fs-14">Class</td>
                                    <td style="width:50%;vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-left: 10px;font-weight: 500;" class="print-fs-14">Test</td>
                                    <td style="width:50%;vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-left: 10px;font-weight: 500;text-align: right;" class="print-fs-14">Cutoff <sup>(ng/mL) </sup></td>
                                 </tr>
                                    @foreach($prescribedNotDetected as $key => $value)

                                    <tr class="table-6-row">
                                       @foreach($tests as $resultValue)
                                          @if($key == $resultValue->dendi_test_name)
                                          <td>
                                             <p style="font-family: 'Open Sans';text-transform:uppercase;font-size:21px;margin: 0px !important;">{{  $resultValue->class }}</p>
                                          </td>
                                          @endif 
                                       @endforeach
                                       <td>
                                       @foreach($tests as $resultValue)
                                          @if($key == $resultValue->dendi_test_name)
                                             @if(empty($resultValue->description))
                                             <p style="font-weight:600;font-family: 'Open Sans';font-size:21px;margin: 0px !important;">{{  $key }}</p> 
                                             @else
                                             <p style="font-weight:600;font-family: 'Open Sans';font-size:21px;margin: 0px !important;">{{  $key }} ({{ $resultValue->description }})</p> 
                                             @endif
                                          @endif  
                                       @endforeach
                                       </td>
                                       <td>
                                       @foreach($tests as $resultValue)
                                          @if($key == $resultValue->dendi_test_name)
                                          <p style="font-family: 'Open Sans';text-align: right;font-size:21px;margin: 0px !important;">{{  $resultValue->LLOQ }}</p>
                                          @endif 
                                       @endforeach
                                       </td> 
                                    </tr>
                                    <tr>
                                       <td colspan="3">
                                       
                                       @foreach($tests as $resultValue)
                                          @if(ucwords($key) == ucwords($resultValue->dendi_test_name)|| $key == $resultValue->dendi_test_name)
                                             @if($resultValue->class == 'NSAID' && strpos($icdCode, 'Chronic kidney disease') !== false)
                                             <p style="color:red;font-family: 'Open Sans';margin: 0px !important;font-size:21px">[CI] NSAID medications reduce blood flow to the kidneys and should be avoided for patients with Chronic Kidney Disease</p>
                                             @endif 
                                          @endif 
                                       @endforeach
                                       @foreach($arrayResult as $name => $value)
                                          @if(ucwords($name) == ucwords($key))
                                             @if(str_contains($value, '[CI]') && ucfirst($name) == "Metformin" && str_contains($value, 'Chronic kidney disease'))
                                                <p style="color:red;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{  $value }} ( {{ $metforminText }} ) </p>
                                             @elseif(str_contains($value, '[CI]'))
                                                @foreach($tests as $resultValue)
                                                   @if($key == ucwords($resultValue->dendi_test_name) || $key == $resultValue->dendi_test_name)
                                                      @if($resultValue->class == "Diuretic" || $resultValue->class == "Antidiabetic" && str_contains($value, 'Chronic kidney disease'))
                                                         <p style="color:red;font-family: 'Open Sans';margin: 0px !important;font-size:21px;display:none !important;"></p> 
                                                      @else
                                                         <p style="color:red;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{  $value }}</p>  
                                                      @endif
                                                   @endif
                                                @endforeach  
                                             @else 
                                             <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{  $value }}</p>
                                             @endif
                                          @endif 
                                       @endforeach
                                       @if(!array_key_exists($key,$contraindicationComments))
                                          @if(is_array($value))
                                             @if(strtoupper($value['class']) == 'NSAID' && !empty($anticoagulantClassTestArray))
                                                <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">[DDI] (Bleeding Risk) {{ implode(",",$anticoagulantClassTestArray['ANTICOAGULANT']) }} </p>   
                                             @endif

                                             @if(strtoupper($value['class']) == 'ANTICOAGULANT' && !empty($nsaidClassTestArray))
                                                <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">[DDI] (Bleeding Risk) {{ implode(",",$nsaidClassTestArray['NSAID']) }}</p>   
                                             @endif
                                          @endif
                                       @endif 

                                       @foreach($contraindicationComments as $keys => $description)
                                          @if(ucwords($keys) == ucwords($key))
                                             @if(is_array($value) && (strtoupper($value['class']) == 'NSAID' || strtoupper($value['class']) == 'ANTICOAGULANT'))
                                                @if(strtoupper($value['class']) == 'NSAID' && !empty($anticoagulantClassTestArray))
                                                   <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{ $description }} - (Bleeding Risk) {{ implode(",",$anticoagulantClassTestArray['ANTICOAGULANT']) }}</p>   
                                                @endif

                                                @if(strtoupper($value['class']) == 'ANTICOAGULANT' && !empty($nsaidClassTestArray))
                                                   <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{ $description }} - (Bleeding Risk) {{ implode(",",$nsaidClassTestArray['NSAID']) }}</p>   
                                                @endif
                                             @else
                                                <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{  $description }}</p>
                                             @endif
                                          @endif 
                                       @endforeach 
                                       
                                       </td>
                                    </tr>
                                    @endforeach
                                 @else
                                    <tr style="margin:0px !important;">
                                       <td colspan="4" style="text-align:center;margin:0px !important;">
                                          <p style="font-family: 'Open Sans';text-transform:uppercase;font-weight:500;font-size:21px">"No Results"</p>
                                       </td>
                                    </tr>
                                 @endif 
                              </table>
                           </td>
                        </tr>
                     </table>
                  </td>
                  <td style="width:30%; border:2px solid #000000;Border-radius:10px; vertical-align:top; corner-radius: 10px;padding: 0;">
                     <table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                        <tr style="background:#000000">
                           <td style="vertical-align:middle;height:40px;font-family: 'Open Sans', sans-serif;font-size: 24px;color:#fff;padding-left: 10px;font-weight: bold;" class="fs-18">VALIDITY TESTING & SCREENS</td>
                        </tr>
                        <tr>
                           <td>
                              <table cellpadding="0" cellspacing="0" border="0" style="width:100%; height:250px;padding:20px;" class="print-height-1">
                                 <tr>
                                    <td style="width:50%; vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-left: 10px;font-weight: 500;" class="print-fs-14">Specimen Validity</td>
                                    <td style="width:25%;vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-right: 10px;font-weight: 500;" class="print-fs-14">Range</td>
                                    <td style="width:25%;vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-left: 10px;font-weight: 500;text-align:right;" class="print-fs-14">Results </td>
                                 </tr>
                                 <tr class="table-6-row">
                                    <td>
                                    <p style="font-family: 'Open Sans';font-size:21px">PH</p>
                                    <p style="font-family: 'Open Sans';font-size:21px">Specific Gravity</p>
                                    <p style="font-family: 'Open Sans';font-size:21px">Urine Creatinine <sup>(mg/dL) </sup></p>
                                    </td>
                                    <td>
                                    <p style="font-family: 'Open Sans';font-size:21px">4.50 - 8.00</p>
                                    <p style="font-family: 'Open Sans';font-size:21px">1.01 - 1.03</p>
                                    <p style="font-family: 'Open Sans';font-size:21px">20.00 - 400.00</p>
                                    </td>
                                    <td>
                                    <p style="font-family: 'Open Sans';text-align:right;font-size:21px">{{ $quantitativeResult }}</p>
                                    <p style="font-family: 'Open Sans';text-align:right;font-size:21px">{{ $quantitativeResultSpecificGravity }}</p>
                                    <p style="font-family: 'Open Sans';text-align:right;font-size:21px">{{ $quantitativeResultCreatinine }}</p>
                                    </td>
                                 </tr>
                                 @if(!empty($panelTestResult))
                                 <tr>
                                    <td style="width:50%; vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-left: 10px;font-weight: 600;" class="print-fs-14">Test</td>
                                    <td style="width:25%;vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-right: 10px;font-weight: 600;" class="print-fs-14">Cutoff</td>
                                    <td style="width:25%;vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-left: 10px;font-weight: 600;text-align:right;" class="print-fs-14">Results </td>
                                 </tr>
                                 @foreach($panelTestResult as $panelTestName => $resultTests)
                                    <tr class="table-6-row">
                                       <td>
                                       <p style="font-family: 'Open Sans';font-size:21px;margin: 0px !important;">{{  $panelTestName }}</p> 
                                       </td>
                                       <td>
                                       @foreach($panelTests as $resultValue)
                                          @if($panelTestName == $resultValue->panel_test_name)
                                             <p style="font-family: 'Open Sans';font-size:21px;margin: 0px !important;">{{  $resultValue->LLOQ }}</p> 
                                          @endif
                                       @endforeach
                                       </td>
                                       <td>
                                       <p style="font-family: 'Open Sans';font-size:21px;margin: 0px !important;text-align:right;">{{  $resultTests }}</p> 
                                       </td>
                                    </tr>
                                 @endforeach
                                 @endif
                              </table>
                           </td>
                        </tr>
                     </table>
                  </td>
               </tr>
               
            </table>
         </td>
      </tr>
   </table>
   <table cellpadding="0" cellspacing="0" Border="0" style="max-width:1320px; width:100%;margin:40px auto;">
      <tr>
         <td>
            <table cellpadding="12" cellspacing="12" border="0" style="width:100%; border-spacing: 15px 0;" class="display-block">
               <tr>
                  <td class="border-radius" style="width:100%; padding:0px !important;border:2px solid #169d53; border-radius:10px !important; vertical-align:top; corner-radius:10px;">
                  <table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                        <tr style="height:30px;">
                           <td colspan="2" style="vertical-align:top; text-align:center; padding:10px 0px;font-family: 'Open Sans', sans-serif; font-size:18px; font-weight:bold; height:30px">CONSISTENT</td>
                        </tr>
                        <tr style="background:#169d53">
                           <td style="vertical-align:middle;height:40px;font-family: 'Open Sans', sans-serif;font-size: 24px;color:#fff;padding-left: 10px;font-weight: bold; " class="fs-18">PRESCRIBED</td>
                           <td style="vertical-align:middle;height:40px;font-family: 'Open Sans', sans-serif;font-size: 24px;color:#fff;padding-right: 10px;font-weight: 400; text-align:right" class="fs-18">DETECTED</td>
                        </tr>
                        <tr>
                           <td colspan="2">
                           <table cellpadding="0" cellspacing="0" border="0" style="width:100%; padding:20px;" class="print-height">
                             
                                 
                              @if(!empty($sortedPrescribedDetected))
                              <tr>
                                 <td style="width:25%; vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-left: 10px;font-weight: 500;" class="print-fs-14">Class</td>
                                 <td style="width:25%;vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-left: 10px;font-weight: 500;" class="print-fs-14">Test</td>
                                 <td style="width:25%;vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-left: 10px;font-weight: 500;text-align: right;" class="print-fs-14">Cutoff <sup>(ng/mL) </sup></td>
                                 <td style="width:25%;vertical-align:top;height:40px;font-family: 'Open Sans', sans-serif;font-size: 20px;color:#000;padding-left: 10px;font-weight: 500;text-align: right; white-space:nowrap" class="print-fs-14">Results <sup>(ng/mL) </sup></td>
                              </tr>
                                 @foreach($sortedPrescribedDetected as $medicineName => $result)
                                 <tr class="table-6-row">
                                    <td>
                                       @foreach($tests as $resultValue)
                                          @if($medicineName == ucwords($resultValue->dendi_test_name) || $medicineName == $resultValue->dendi_test_name)
                                          <p style="font-family: 'Open Sans';text-transform:uppercase;font-size:21px;margin: 0px !important;">{{  $resultValue->class }}</p>
                                          @endif 
                                       @endforeach
                                    </td>
                                    <td style="width:40%;">
                                       @foreach($tests as $resultValue)
                                       @if($medicineName == ucwords($resultValue->dendi_test_name)|| $medicineName == $resultValue->dendi_test_name)
                                          @if(!empty($resultValue->description))
                                             <p style="font-weight:600;font-family: 'Open Sans';margin: 0px !important;font-size:21px;margin: 0px !important;">{{  $medicineName }} ({{ $resultValue->description }})</p>
                                          @else
                                             <p style="font-weight:600;font-family: 'Open Sans';font-size:21px;margin: 0px !important;">{{  $medicineName }} </p>
                                          @endif 
                                       @endif 
                                       @endforeach
                                       
                                    </td>
                                    <td>
                                       
                                       @foreach($tests as $resultValue)
                                          @if($medicineName == ucwords($resultValue->dendi_test_name)|| $medicineName == $resultValue->dendi_test_name)
                                          <p style="font-family: 'Open Sans';text-align: right;font-size:21px;margin: 0px !important;">{{  $resultValue->LLOQ }}</p>
                                          @endif 
                                       @endforeach
                                       
                                    </td>
                                    <td>
                                       @if($result['result_qualitative'] == 'Detected')
                                          @foreach($tests as $resultValue)
                                             @if($medicineName == ucwords($resultValue->dendi_test_name)|| $medicineName == $resultValue->dendi_test_name)
                                                @if($result['result_quantitative'] > $resultValue->ULOQ)
                                                   <p style="font-family: 'Open Sans';text-align: right;font-size:21px;margin: 0px !important;">{{  " > ". $resultValue->ULOQ  }}</p>
                                                @else
                                                   <p style="font-family: 'Open Sans';text-align: right;font-size:21px;margin: 0px !important;">{{  number_format((float)$result['result_quantitative'], 2, '.', '') }}</p>
                                                @endif 
                                             @endif 
                                          @endforeach
                                       @else
                                       <p style="font-family: 'Open Sans';text-align: right;font-size:21px;margin: 0px !important;">{{  $result['result_qualitative'] }}</p>
                                       @endif 
                                       
                                    </td> 
                                 </tr>
                                 <tr>
                                    <td colspan="4">
                                    @foreach($tests as $resultValue)
                                       @if(ucwords($medicineName) == ucwords($resultValue->dendi_test_name)|| $medicineName == $resultValue->dendi_test_name)
                                          @if($resultValue->class == 'NSAID' && strpos($icdCode, 'Chronic kidney disease') !== false)
                                          <p style="color:red;font-family: 'Open Sans';margin: 0px !important;font-size:21px">[CI] NSAID medications reduce blood flow to the kidneys and should be avoided for patients with Chronic Kidney Disease</p>
                                          @endif 
                                       @endif 
                                    @endforeach
                                    @foreach($arrayResult as $name => $value)
                                       @if(ucwords($name) == ucwords($medicineName))
                                             @if(str_contains($value, '[CI]') && ucfirst($name) == "Metformin" && str_contains($value, 'Chronic kidney disease'))
                                                <p style="color:red;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{  $value }} ( {{ $metforminText }} ) </p>
                                                @elseif(str_contains($value, '[CI]'))
                                                   @foreach($tests as $resultValue)
                                                      @if(ucwords($medicineName) == ucwords($resultValue->dendi_test_name) || $medicineName == $resultValue->dendi_test_name)
                                                         @if($resultValue->class == "Diuretic" || $resultValue->class == "Antidiabetic" && str_contains($value, 'Chronic kidney disease'))
                                                            <p style="color:red;font-family: 'Open Sans';margin: 0px !important;font-size:21px;display:none !important;"></p>
                                                         @else
                                                            <p style="color:red;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{  $value }}</p>  
                                                         @endif
                                                      @endif
                                                   @endforeach  
                                                @elseif(str_contains($value, 'Salicylic acid') && str_contains($value, 'Acetylsalicylic acid'))
                                                   <p style="color:#FF4500;display:none !important;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{  $value }}</p>
                                                @else 
                                                <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{  $value }}</p>
                                             @endif
                                       @endif 
                                    @endforeach
                                    @if(!array_key_exists($medicineName,$contraindicationComments))
                                       @if(is_array($result))
                                          @if(strtoupper($result['class']) == 'NSAID' && !empty($anticoagulantClassTestArray))
                                             <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">[DDI] (Bleeding Risk) {{ implode(",",$anticoagulantClassTestArray['ANTICOAGULANT']) }} </p>   
                                          @endif

                                          @if(strtoupper($result['class']) == 'ANTICOAGULANT' && !empty($nsaidClassTestArray))
                                             <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">[DDI] (Bleeding Risk) {{ implode(",",$nsaidClassTestArray['NSAID']) }}</p>   
                                          @endif
                                       @endif
                                    @endif 
                                    @foreach($contraindicationComments as $keys => $description)
                                       @if(ucwords($keys) == ucwords($medicineName))
                                          @if(is_array($result) && (strtoupper($result['class']) == 'NSAID' || strtoupper($result['class']) == 'ANTICOAGULANT'))
                                             @if(strtoupper($result['class']) == 'NSAID' && !empty($anticoagulantClassTestArray))
                                                <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{ $description }} - (Bleeding Risk) {{ implode(",",$anticoagulantClassTestArray['ANTICOAGULANT']) }}</p>   
                                             @endif

                                             @if(strtoupper($result['class']) == 'ANTICOAGULANT' && !empty($nsaidClassTestArray))
                                                <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{ $description }} - (Bleeding Risk) {{ implode(",",$nsaidClassTestArray['NSAID']) }}</p>   
                                             @endif
                                          @else
                                             <p style="color:#FF4500;font-family: 'Open Sans';margin: 0px !important;font-size:21px">{{  $description }}</p>
                                          @endif
                                       @endif 
                                    @endforeach
                                    </td>
                                 </tr>
                                 @endforeach
                              @else
                                 <tr>
                                    <td colspan="4" style="text-align:center;">
                                       <p style="font-family: 'Open Sans';text-transform:uppercase;font-weight:500;font-size:21px">"No Results"</p>
                                    </td>
                                 </tr>
                              @endif 
                           </table>
                           </td>
                        </tr>
                     </table>
                  </td>
               </tr>
            </table>
         </td>
      </tr>
   </table>
   <table cellpadding="0" cellspacing="0" Border="0" style="max-width:1320px; width:100%;margin:40px auto;">
      <tr>
         <td>
            <table cellpadding="12" cellspacing="12" border="0" style="width:100%; border-spacing: 15px 0;" class="display-block">
               <tr>
                  <td class="border-radius" style="width:100%; padding:0px !important;border:2px solid #084ba0; border-radius:10px !important; vertical-align:top; corner-radius:10px;">
                  <table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                           <tr style="background:#084ba0">
                              <td style="vertical-align:middle;font-family: 'Open Sans', sans-serif;font-size: 24px;color:#fff;padding-left: 10px;font-weight: bold;" class="fs-18">NOT PRESCRIBED</td>
                              <td style="vertical-align:middle;font-family: 'Open Sans', sans-serif;font-size: 24px;color:#fff;padding-right: 10px;font-weight: 400; text-align:right" class="fs-18">NOT DETECTED</td>
                           </tr>
                           <tr>
                              <td colspan="2">
                                 <table cellpadding="0" cellspacing="0" border="0" style="width:100%; height:100px; border-collapse:collapse" class="print-height">
                                    <tr>
                                    @foreach($notDetectednotPrescribed->chunk(3) as $chunk)
                                       <td style="width:50%; vertical-align:top;font-family: 'Open Sans', sans-serif;font-size: 17px;color:#000;padding: 0px 10px;font-weight: 400;border:2px solid #084ba0;" class="print-fs-14">
                                          <table cellpadding="0" cellspacing="0" border="0" style="width:100%;Border-radius:10px;corner-radius:10px;">
                                          
                                          <tr>
                                             <td style="font-family: 'Open Sans', sans-serif; font-size:20px; width:50%;font-weight:500;" class="print-fs-14">Test</td>
                                             <td style="font-family: 'Open Sans', sans-serif; font-size:20px; width:50%;font-weight:500;text-align: right;" class="print-fs-14">Cutoff <sup>(ng/mL) </sup></td>
                                          </tr>
                                             @if(!empty($chunk))
                                                @foreach($chunk as $values)
                                                   @foreach($values as $key => $value)
                                                   <tr class="table-6-row" style="padding:0px !important;margin:0px !important;">
                                                      <td>
                                                      <p style="font-family: 'Open Sans';font-size:17px; margin:0">{{  $key }}</p>
                                                      </td>
                                                      <td>
                                                      
                                                      @foreach($tests as $resultValue)
                                                         @if($key == $resultValue->dendi_test_name)
                                                         <p style="font-family: 'Open Sans';text-align: center;font-size:17px;margin:0">{{  $resultValue->LLOQ }}</p>
                                                         @endif 
                                                      @endforeach
                                                      
                                                      </td>
                                                   </tr>
                                                   @endforeach
                                                @endforeach
                                             @endif 
                                          </table>
                                       </td>
                                    @endforeach
                                    </tr>
                                 </table>
                              </td>
                           </tr>
                        
                        </table>
                  </td>
               </tr>
            </table>
         </td>
      </tr>
   </table>
  
        
      
   <footer>
   <p><strong>IMPORTANT DISCLAIMER NOTICE :  </strong>Newstar warrants that all lab analysis provided is conducted professionally in accordance with all applicable standard laboratory practices and that this data reflects Newstar's attempt to generate accurate results for the specific sample(s) submitted to generate this report. Newstar has developed the tests used during the lab analysis and determined their performance characteristics. The tests have not been cleared or approved by the Food and Drug Administration. The results provided are based on information provided by the Customer. Report results are contingent on the accuracy of the prescription list, diagnosis codes, and government databases used to determine drug contraindications and drug interactions. This data reflects Newstar's attempt to generate accurate results based on the information that was provided to Newstar by the Customer and relying on the established government databases available to Newstar for the specific sample(s). Newstar disclaims any and all liability for any errors and is not responsible for any claims or damages related to the reliability of the test results. </p>
   </footer>
      
   </body>
</html>


