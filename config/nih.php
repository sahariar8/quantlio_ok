<?php

$baseURL = 'https://rxnav.nlm.nih.gov/REST';
$umlBaseUrl = 'http://umlsks.nlm.nih.gov';
$utsBaseUrl = 'https://uts.nlm.nih.gov/uts/';
$crosswalk = 'https://uts-ws.nlm.nih.gov/';
$fdaBaseUrl = 'https://api.fda.gov/';
$dendiSoftware = 'https://newstar.dendisoftware.com/';
$utsLoginBaseUrl = 'https://utslogin.nlm.nih.gov/';
$newStarAnalytics = 'https://uat.newstaranalytics.com/';
//** Link needed for Stratus */
$stratus_requestQueue = 'https://testapi.stratusdx.net/interface/results';
$stratus_ack_api = 'https://testapi.stratusdx.net/interface/order/received';   // https://testapi.stratusdx.net/interface/order/received/233549-5c7f2a87-3ac8-47a9-bee0-9c6dabbbdeaa/ack
$stratus_post_base64_report_pdf_api = 'https://testapi.stratusdx.net/interface/result/upload/base64';

//*** pdf generation url  */
$stratus_pdf_generation_api = 'http://127.0.0.1:8000/api/generate-report?order_code=';


return [
    'baseUrl' => $baseURL,
    'umlUrl' => $umlBaseUrl,
    'utsBaseUrl' => $utsBaseUrl,
    'newStarAnalytics' => $newStarAnalytics,
    'token' => 'Token bcd4254db2a7e2506ad8c49d9e2f24e61e2abd89',
    'fromDate' => 20200101,
    'apikey' => 'b48227a9-a4bd-44b9-bcb0-83ac29654d6a',
    'utsApiKey' => $utsLoginBaseUrl .'cas/v1/api-key',
    'fdaBaseUrl' => $fdaBaseUrl . 'drug/label.json?search=openfda.substance_name:',
    'fdaWarnings' => $fdaBaseUrl . 'drug/label.json?search=contraindications:',
    'crosswalk' => $crosswalk .'rest/crosswalk/current/source/ICD10CM/',
    'dendiSoftwareId' => $dendiSoftware .'api/v1/test_targets/',
    'dendiSoftwareTestResults' => $dendiSoftware .'api/v2/orders/test_results/?order_code=',
    'dendiSoftwareOrders' => $dendiSoftware.'api/v1/orders/?code=',
    'rxcui' => $baseURL . '/rxcui.json',
    'testComments' => $dendiSoftware . '/api/v1/tests/comments/',
    'sampleComments' => $dendiSoftware . '/api/v1/samples/comments/',
    'rxcuiList' => $baseURL . '/interaction/list.json?rxcuis=',
    'conditions' => $baseURL . '/rxclass/classMembers.json?classId=',
    'stratus_requestQueue' => $stratus_requestQueue,
    'stratusUserName' => 'salvus_stratusdx_11',
    'stratusPassword' => '86c7d164-7ad3',
    'stratus_ack_api' => $stratus_ack_api,
    'stratus_post_base64_report_pdf_api' => $stratus_post_base64_report_pdf_api,
    'stratusUserName_base64_api' => 'test_purpose_only',
    'stratusPassword_base64_api' => 'test-purpose-only',
    'stratus_pdf_generation_api' => $stratus_pdf_generation_api,
];