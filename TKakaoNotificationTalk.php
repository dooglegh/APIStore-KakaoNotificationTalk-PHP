/* * **************************************************************************
 * 
 * MIT License
 * 
 * APISTORE.CO.KR - Kakao Notifiaction Talk API Plugin For PHP
 * 
 * Created by doogle@naver.com (Shin, dong-hoon) @ 2017-12-12
 *
 * Blog : https://doogle.link/
 * 
 * APISTORE 카카오알림톡 API 구현체
 * 발송 및 결과 확인 등의 API 를 구현하였다.
 * 
 * ***************************************************************************
 *
 * https://www.apistore.co.kr/api/apiProviderGuide.do?service_seq=558
 * 이 소스코드는 카카오 알림톡 API 사용자 가이드 (ver 1.5.2) 기반으로 제작하였습니다.
 * 
 * API 문서 변동 사항
 * 
 * 2017.11.09 (v1.5) : 템플릿 버튼 5 개 추가, 템플릿 조회 API, Response 추가
 * 
 */

namespace TKakaoNotificationTalk;

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // For use Unirest package...


/*
 * 발송 처리 결과 코드
 * 
 */

const POST_MESSAGE_RESULT_USER_ERROR = 100; // User Error
const POST_MESSAGE_RESULT_OK = 200; // OK
const POST_MESSAGE_RESULT_PARAM_ERROR = 300; // Parameter Error
const POST_MESSAGE_RESULT_FAILED_ERROR = 301; // failed_type 이 "N" 이 아닌 경우에 실패 SMS 제목과 내용이 없으면 에러
const POST_MESSAGE_RESULT_BTN_ERROR = 302; // 버튼 2개이상 발송시에 버튼 정보가 없는 템플릿에 대한 에러
const POST_MESSAGE_RESULT_ETC_ERROR = 400; // Etc Error
const POST_MESSAGE_RESULT_UNREGISTERED_NUMBER_ERROR = 500; // 발신번호 사전 등록제에 의한 미등록차단
const POST_MESSAGE_RESULT_POINT_ERROR = 600; // 충전요금 부족
const POST_MESSAGE_RESULT_NOT_CONFIRMED_TEMPLATE_ERROR = 700; // 템플릿코드 사전 승인제에 의한 미승인 차단
const POST_MESSAGE_RESULT_TEMPLATE_ERROR = 800; // 템플릿코드에러
const POST_MESSAGE_RESULT_PROFILE_ERROR = 900; // 프로파일이 존재하지 않음

$PostMessageResults = [
  POST_MESSAGE_RESULT_USER_ERROR => 'User Error',
  POST_MESSAGE_RESULT_OK => 'OK',
  POST_MESSAGE_RESULT_PARAM_ERROR => 'Parameter Error',
  POST_MESSAGE_RESULT_FAILED_ERROR => 'failed_type 이 "N" 이 아닌 경우에 실패 SMS 제목과 내용이 없으면 에러',
  POST_MESSAGE_RESULT_BTN_ERROR => '버튼 2개이상 발송시에 버튼 정보가 없는 템플릿에 대한 에러',
  POST_MESSAGE_RESULT_ETC_ERROR => 'Etc Error',
  POST_MESSAGE_RESULT_UNREGISTERED_NUMBER_ERROR => '발신번호 사전 등록제에 의한 미등록차단',
  POST_MESSAGE_RESULT_POINT_ERROR => '충전요금 부족',
  POST_MESSAGE_RESULT_NOT_CONFIRMED_TEMPLATE_ERROR => '템플릿코드 사전 승인제에 의한 미승인 차단',
  POST_MESSAGE_RESULT_TEMPLATE_ERROR => '템플릿코드에러',
  POST_MESSAGE_RESULT_PROFILE_ERROR => '프로파일이 존재하지 않음',
];


/*
 * REPORT CODE
 */

const RSLT_SUCCESS = 0;
const RSLT_INVALID_SENDER_KEY = 1;
const RSLT_NOT_CONNECTED_USER = 2;
const RSLT_ACK_TIMEOUT = 5;
const RSLT_RESPONSE_HISTORY_NOTFOUND = 6;
const RSLT_NO_SEND_AVALIABLE_STATUS = 8;
const RSLT_NO_VISIT_USER = 9;
const RSLT_E_INSUFFICIENT = 'a';
const RSLT_E_REJECT_ITER = 'b';
const RSLT_E_DUP_KEY = 'c';
const RSLT_E_DUP_PHONE = 'd';
const RSLT_E_SERVER_ERROR = 'e';
const RSLT_E_FORMAT_ERR = 'f';
const RSLT_MESSAGE_NOT_FOUND = 'k';
const RSLT_E_TIMEOUT_AGENT = 'o';
const RSLT_E_DUPLICATE_PHONE_MSG = 'p';
const RSLT_MESSAGE_EMPTY = 't';
const RSLT_NO_USER = 'A';
const RSLT_USER_BLOCKED_DELIVERY = 'B';
const RSLT_SERIAL_NUMBER_DUPLICATED = 'C';
const RSLT_DUPLICATED = 'D';
const RSLT_UNSUPPORTED_CLIENT = 'E';
const RSLT_ETC = 'F';
const RSLT_FAILED_TO_SEND_MESSAGE = 'H';
const RSLT_INVALID_PHONE_NUMBER = 'I';
const RSLT_SAFETY_PHONE_NUMBER = 'J';
const RSLT_MESSAGE_LENGTH_OVER_LIMIT = 'L';
const RSLT_TEMPLATE_NOT_FOUND = 'M';
const RSLT_SPAM = 'S';
const RSLT_NO_MATCHED_TEMPLATE = 'U';
const RSLT_FAILED_TO_MATCH_TEMPLATE = 'V';

$ResultMessages = [
  RSLT_SUCCESS => '성공',
  RSLT_INVALID_SENDER_KEY => '발신 프로필 키가 유효하지않음',
  RSLT_NOT_CONNECTED_USER => '서버와 연결되어있지않은 사용자',
  RSLT_ACK_TIMEOUT => '메시지 발송 후 수신여부 불투명',
  RSLT_RESPONSE_HISTORY_NOTFOUND => '메시지 전송결과를 찾을 수 없음',
  RSLT_NO_SEND_AVALIABLE_STATUS => '메시지를 전송할 수 없는 상태',
  RSLT_NO_VISIT_USER => '최근 카카오톡을 미사용자',
  RSLT_E_INSUFFICIENT => '건수 부족',
  RSLT_E_REJECT_ITER => '전송 권한 없음',
  RSLT_E_DUP_KEY => '중복된 키 접수 차단',
  RSLT_E_DUP_PHONE => '중복된 수신번호 접수 차단',
  RSLT_E_SERVER_ERROR => '서버 내부 에러',
  RSLT_E_FORMAT_ERR => '메시지 포맷 에러',
  RSLT_MESSAGE_NOT_FOUND => '메시지가존재하지않음',
  RSLT_E_TIMEOUT_AGENT => 'TIME OUT 처리(Agent 내부)',
  RSLT_E_DUPLICATE_PHONE_MSG => '메시지본문 중복 차단(Agent 내부)',
  RSLT_MESSAGE_EMPTY => '메시지가비어있음',
  RSLT_NO_USER => '카카오톡을 미사용자',
  RSLT_USER_BLOCKED_DELIVERY => '알림톡 차단을 선택한 사용자',
  RSLT_SERIAL_NUMBER_DUPLICATED => '메시지 일련번호 중복',
  RSLT_DUPLICATED => '5 초 이내 메시지 중복 발송',
  RSLT_UNSUPPORTED_CLIENT => '미지원 클라이언트 버전',
  RSLT_ETC => '기타 오류',
  RSLT_FAILED_TO_SEND_MESSAGE => '카카오 시스템 오류',
  RSLT_INVALID_PHONE_NUMBER => '전화번호 오류',
  RSLT_SAFETY_PHONE_NUMBER => '050 안심번호 발송불가',
  RSLT_MESSAGE_LENGTH_OVER_LIMIT => '메시지 길이 제한 오류',
  RSLT_TEMPLATE_NOT_FOUND => '템플릿을 찾을 수 없음',
  RSLT_SPAM => '발신번호 검증 오류',
  RSLT_NO_MATCHED_TEMPLATE => '메시지가 템플릿과 일치하지않음',
  RSLT_FAILED_TO_MATCH_TEMPLATE => '메시지가 템플릿과 비교 실패',
];


/*
 * 에러 메세지
 * MSG_RSLT
 * 
 */

const E_OK = 0; // 성공
const E_TIMEOUT = 1; // 전송시간 초과
const E_INVALID_PHONE = 2; // 잘못된 전화번호/비가입자
const E_NOT_ACK = 5; // 통신사 결과 미수신
const E_PHONE_BUSY = 8; // 단말기 BUSY
const E_SHADOW_REGION = 9; // 음영지역
const E_INSUFFICIENT = 'a'; // 건수 부족
const E_REJECT_ITER = 'b'; // 전송 권한 없음
const E_DUP_KEY = 'c'; // 중복된 키 접수 차단
const E_DUP_PHONE = 'd'; // 중복된 수신번호 접수 차단
const E_SERVER_ERROR = 'e'; // 서버 내부 에러
const E_TIMEOUT_AGENT = 'o'; // TIME OUT 처리(Agent 내부)
const E_DUPLICATE_PHONE_MSG = 'p'; // 메시지본문 중복 차단(Agent 내부)
const E_DUPLICATE_KEY = 'q'; // 메시지 중복키 체크(Agent 내부)
const E_TOGETHER_COUNT = 't'; // 잘못된 동보 전송 수신번호 리스트 카운트(Agent 내부)
const E_MSG_FULL = 'A'; // 단말기 메시지 저장개수 초과
const E_SERVICE_STOP = 'B'; // 단말기 일시 서비스 정지
const E_ETC_PHONE = 'C'; // 기타 단말기 문제
const E_DENY = 'D'; // 착신 거절
const E_POWER_OFF = 'E'; // 전원 꺼짐
const E_ETC = 'F'; // 기타
const E_INNER_FORMAT_ERR = 'G'; // 내부 포맷 에러
const E_TELCO_FORMAT_ERR = 'H'; // 통신사 포맷 에러
const E_UNACCEPTED_PHONE = 'I'; // SMS/MMS 서비스 불가 단말기
const E_MSG_FAIL = 'J'; // 착신 측 호 불가 상태
const E_TELCO_MSG_DEL = 'K'; // 통신사에서 메시지 삭제 처리
const E_TELCO_QUE_FULL = 'L'; // 통신사 메시지 처리 불가 상태
const E_WIRELESS_FAIL = 'M'; // 무선망단 전송 실패
const E_SPAM = 'S'; // 스팸
const E_CONTENTS_SIZE_ERR = 'V'; // 컨텐츠 사이즈 초과
const E_CONTENTS_ERR = 'U'; // 잘못된 컨텐츠

$ErrorMessages = [
  E_OK => '성공',
  E_TIMEOUT => '전송시간 초과',
  E_INVALID_PHONE => '잘못된 전화번호/비가입자',
  E_NOT_ACK => '통신사 결과 미수신',
  E_PHONE_BUSY => '단말기 BUSY',
  E_SHADOW_REGION => '음영지역',
  E_INSUFFICIENT => '건수 부족',
  E_REJECT_ITER => '전송 권한 없음',
  E_DUP_KEY => '중복된 키 접수 차단',
  E_DUP_PHONE => '중복된 수신번호 접수 차단',
  E_SERVER_ERROR => '서버 내부 에러',
  E_TIMEOUT_AGENT => 'TIME OUT 처리(Agent 내부)',
  E_DUPLICATE_PHONE_MSG => '메시지본문 중복 차단(Agent 내부)',
  E_DUPLICATE_KEY => '메시지 중복키 체크(Agent 내부)',
  E_TOGETHER_COUNT => '잘못된 동보 전송 수신번호 리스트 카운트(Agent 내부)',
  E_MSG_FULL => '단말기 메시지 저장개수 초과',
  E_SERVICE_STOP => '단말기 일시 서비스 정지',
  E_ETC_PHONE => '기타 단말기 문제',
  E_DENY => '착신 거절',
  E_POWER_OFF => '전원 꺼짐',
  E_ETC => '기타',
  E_INNER_FORMAT_ERR => '내부 포맷 에러',
  E_TELCO_FORMAT_ERR => '통신사 포맷 에러',
  E_UNACCEPTED_PHONE => 'SMS/MMS 서비스 불가 단말기',
  E_MSG_FAIL => '착신 측 호 불가 상태',
  E_TELCO_MSG_DEL => '통신사에서 메시지 삭제 처리',
  E_TELCO_QUE_FULL => '통신사 메시지 처리 불가 상태',
  E_WIRELESS_FAIL => '무선망단 전송 실패',
  E_SPAM => '스팸',
  E_CONTENTS_SIZE_ERR => '컨텐츠 사이즈 초과',
  E_CONTENTS_ERR => '잘못된 컨텐츠',
];

const REPORT_SEND_STATUS_READY = 1;
const REPORT_SEND_STATUS_SENDED = 2;
const REPORT_SEND_STATUS_RESULT_RECEIVED = 3;

$ReportSendStatus = [
  REPORT_SEND_STATUS_READY => "발송대기",
  REPORT_SEND_STATUS_SENDED => "전송완료",
  REPORT_SEND_STATUS_RESULT_RECEIVED => "결과수신완료",
  // 문서에 명시적으로 나와있지는 않지만 발송 에러의 경우 상태값은 4 가된다. (msg_rstl= F (기타), rstl = U (잘못된 컨텐츠)
];

const REGISTER_SEND_RESULT_CODE_SUCCESS = "200";
const REGISTER_SEND_RESULT_CODE_PARAMETER_ERROR = "300";
const REGISTER_SEND_RESULT_CODE_AUTH_UPDATE_ERROR = "400";
const REGISTER_SEND_RESULT_CODE_ALEADY_RESISTERED_NUMBER = "500";
const REGISTER_SEND_RESULT_CODE_AUTH_NUMBER_ERROR = "600";
const REGISTER_SEND_RESULT_CODE_TIMEOUT_ERROR = "700";

$RegisterSendResultCode = [
  REGISTER_SEND_RESULT_CODE_SUCCESS => "성공",
  REGISTER_SEND_RESULT_CODE_PARAMETER_ERROR => "파라메터 에러",
  REGISTER_SEND_RESULT_CODE_AUTH_UPDATE_ERROR => "인증 업데이트 중 에러",
  REGISTER_SEND_RESULT_CODE_ALEADY_RESISTERED_NUMBER => "이미 등록된 번호",
  REGISTER_SEND_RESULT_CODE_AUTH_NUMBER_ERROR => "일치 하지 않는 인증번호",
  REGISTER_SEND_RESULT_CODE_TIMEOUT_ERROR => "핀코드 인증 시간 만료(3분 이후 만료이며 재등록 요청해야 함.)",
];

/*
 * API 목록
 * 
 * 발송        /kko/{v1}/msg/{client_id}               카카오 알림톡 발송 기능
 * 결과조회    /kko/{v1}/report/{client_id}            결과 조회 기능
 * 템플릿 조회 /kko/{v1}/template/list/{client_id}     등록된 템플릿 조회
 * 발신번호    /kko/{v1}/sendernumber/save/{client_id} 발신번호 등록
 *             /kko/{v1}/sendernumber/list/{client_id} 등록된 발신번호 리스트 조회
 */

class TKakaoNotificationTalk {

  // 구매 시 발급받은 Key의 코드값을 헤더 “x-waple-authorization”의 값으로 설정
  private $key = ""; // "고객 키"
  public $client_id = ""; // {client_id}
  public $kakaoPlusFriendClientId = ""; // 카카오 플러스친구 아이디
  public $defaultCallBack = ""; // 기본 회신 연락처
  public $apiUrls = array();
  public $timeOut = 10; // seconds...

  public function __construct($key, $client_id, $kakaoPlusFriendClientId, $defaultCallBack) {
    $this->key = $key;
    $this->client_id = $client_id;
    $this->kakaoPlusFriendClientId = $kakaoPlusFriendClientId;
    $this->defaultCallBack = $defaultCallBack;
    $this->apiUrls = [
      "발송" => "https://api.apistore.co.kr/kko/1/msg/" . $this->client_id,
      "결과조회" => "https://api.apistore.co.kr/kko/1/report/" . $this->client_id,
      "템플릿조회" => "https://api.apistore.co.kr/kko/1//template/list/" . $this->client_id,
      "발신번호등록" => "https://api.apistore.co.kr/kko/2/sendnumber/save/" . $this->client_id,
      "발신번호목록" => "https://api.apistore.co.kr/kko/1/sendnumber/list/" . $this->client_id,
    ];
  }

  // 메세지 발송...
  /* INPUT : $params
   * PHONE          String Yes 수신할 핸드폰번호
   * CALLBACK       String Yes 발신자 전화번호(“-“제외/숫자만 입력) “01012345678”
   * REQDATE        String Yes 발송시간(없을 경우 즉시발송) “20130529171111”(2013-05-29-17:11:11)
   * MSG            String Yes 전송할 메시지
   * TEMPLATE_CODE  String Yes 카카오 알림톡 템플릿코드
   * FAILED_TYPE    String No  카카오알림톡 전송 실패 시 전송할 메시지 타입 ( SMS, LMS, N )
   * URL            String No  알림톡 버튼 타입 URL (승인된 template 과 불일치시 전송실패), (2017-11-09 업데이트 이전 버튼 1 개 템플릿 호환)
   * URL_BUTTON_TXT String No  알림톡 버튼 타입 버튼 TEXT (승인된 template 과 불일치시 전송실패), (2017-11-09 업데이트 이전 버튼 1개 템플릿 호환)
   * FAILED_SUBJECT String No  카카오알림톡 전송 실패 시 전송할 제목 (SMS 미사용)
   * FAILED_MSG     String No  카카오알림톡 전송 실패 시 전송할 내용
   * BTN_TYPES      String No  카카오 알림톡 버튼타입 (웹링크, 앱링크, 봇키워드,메시지전달, 배송조회), 최대 5개까지 추가 가능하며 타입별 콤마로 구분함.
   * BTN_TXTS       String No  카카오 알림톡 버튼 TEXT ( BNT_TYPES 입력순으로 버튼 TEXT 입력, 승인된 템플릿과 불일치시 전송실패), TEXT별 콤마로 구분함.
   * BTN_URLS1      String No  알림톡 버튼타입 URL (2017-11-09 업데이트 이후 버튼 5개 추가시 필수) 버튼 URL 링크가2개 이상일 경우
   *                           (예제: BTN_URLS1, BTN_URLS2로 파라미터 추가), 콤마로 구분하며 웹링크, 앱링크는 URL이 필수이며, 기타(배송조회, 봇키워드, 메시지전달)은 null 값임. 
   * 
   * OUTPUT
   * 
   * Failed Type
   * RESULT_CODE String No 처리 결과 코드 ($PostMessageResults 참조)
   * RESULT_MSG  String No 잔금 부족[미발송 목록] 01012345678
   * SERIAL_NUM  String No 서버에서 생성한 request를 식별할 수 있는 유일한 키
   * 
   * 
   * < 사용 예제 >
   * 
   * REQUEST
   * URL http://api.apistore.co.kr/kko/1/msg/{client_id}
   * POST
   * Content-Type: application/x-www-form-urlencoded; charset=UTF-8
   * Header
   * x-waple-authorization:MS0xMzY1NjY2MTAyNDk0LTA2MWE4ZDgyLTZhZmMtNGU5OS05YThkLTgyNmFmYzVlOTkzZQ==
   * Parameter
   * PHONE=01011112222
   * CALLBACK=0211112222
   * MSG=테스트
   * TEMPLATE_CODE=A1111
   * BTN_TYPES=웹링크,배송조회,웹링크
   * BTN_TXTS=홈페이지,배송조회,홈페이지
   * BTN_URLS1= https://www.apistore.co.kr, ,https://www.apistore.co.kr (버튼링크가 1개일 경우)
   * FAILED_SUBJECT=테스트
   * FAILED_MSG=테스트
   * 
   * RESONSE
   * 200
   * Access-Control-Allow-Origin
   * Content-Type: application/json
   * {“result_code”:”200”,”cmid”:”2017052411064978”}
   * 
   */
  public function postMessage($params = array()) {
    if (!$params['callback']) {
      $params['callback'] = $this->defaultCallBack;
    }

    if (!$params['apiVersion']) {
      $params['apiVersion'] = 1;
    }

    if (!$params['client_id']) {
      $params['client_id'] = $this->kakaoPlusFriendClientId;
    }

    return( $this->post($this->apiUrls['발송'], $params) );
  }

  /*
   * 발송 결과 조회
   * 
   * 요청 파라미터
   * Element Type    Optional Description
   * CMID    String  No       서버에서 생성한 request를 식별할 수 있는 유일한 키
   * 
   * 반환값
   * Element  Type   Optional Description
   * STATUS   String No       발송상태 1: 발송대기 2: 전송완료 3: 결과수신완료
   * PHONE    String No       수신한 핸드폰번호
   * CALLBACK String No       발신자 전화번호
   * RSLT     String Yes      카카오알림톡 결과수신 ($ResultMessages 참조)
   * MSG_RSLT String Yes      카카오알림톡 실패 시 메시지 결과수신 ($ErrorMessages 참조)
   * 
   * 
   * 사용 예제
   * 
   * REQUEST 
   * URL http://api.apistore.co.kr/kko/1/report/{client_id}?CMID=2017052411064978
   * GET
   * Content-Type: application/x-www-form-urlencoded; charset=UTF-8
   * Header
   * x-waple-authorization:MS0xMzY1NjY2MTAyNDk0LTA2MWE4ZDgyLTZhZmMtNGU5OS05YThkLTgyNmFmYzVlOTkzZQ==
   * 
   * RESONSE
   * 200
   * Access-Control-Allow-Origin: *
   * Content-Type: application/json
   * {"PHONE":"01011112222","RSLT":"0","CALLBACK":"0232894122","MSG_RSLT":"00","STATUS":"3","CMID":"2017052411064978"}
   * 
   */

  public function getReport($params = array()) {
    // "결과조회"     => "https://api.apistore.co.kr/kko/1/report/" . $this->client_id,

    if (!$params['cmid']) {
      throw new \Exception("CMID 값이 비어있습니다.");
    }

    return( $this->get($this->apiUrls['결과조회'], $params) );
  }

  public function getReportStatusText($status) {
    global $ReportSendStatus;
    return( $ReportSendStatus[$status] );
  }

  public function getReportResultText($rslt) {
    global $ResultMessages;
    return( $ResultMessages[$rslt] );
  }

  public function getReportMsgResultText($msg_rslt) {
    global $ErrorMessages;
    return( $ErrorMessages[$msg_rslt] );
  }

  static public $templateStatus = [
    1 => '반환 등록',
    2 => '검수요청',
    3 => '승인',
    4 => '반려',
    5 => '승인중단',
  ];

  /*
   * 템필릿 조회
   * 
   * 요청 파라미터
   * Element       Type   Optional Description
   * TEMPLATE_CODE String Yes      템플릿코드 – 입력 안 할경우 전체 리스트 반환
   * STATUS        String Yes      검수상태 – 입력 안 할경우 전체 리스트 
   *                               반환 등록(1) / 검수요청(2) / 승인(3) / 반려(4) / 승인중단(5)
   * 
   * 반환값
   * Element           Type   Optional Description
   * TEMPLATE_CODE     String No       템플릿코드
   * TEMPLATE_NAME     String No       템플릿명
   * TEMPLATE_MSG      String No       템플릿 내용
   * STATUS            String No       등록상태(등록,검수요청,승인,반려)
   * BTNLIST           String No       버튼 리스트
   * TEMPLATE_BTN_URL  String No       버튼 URL
   * TEMPLATE_BTN_NAME String No       버튼이름
   * TEMPLATE_BTN_TYPE String No       버튼 타입
   * 
   * 사용 예제
   * 
   * REQUEST
   * URL http://api.apistore.co.kr/kko/1/template/list/{client_id}
   * GET
   * Content-Type: application/x-www-form-urlencoded; charset=UTF-8
   * Header
   * x-waple-authorization:
   * MS0xMzY1NjY2MTAyNDk0LTA2MWE4ZDgyLTZhZmMtNGU5OS05YThkLTgyNmFmYzVlOTkzZQ==
   * Parameter
   * TEMPLATE_CODE=api123&status=1
   * 
   * RESONSE
   * 200
   * Access-Control-Allow-Origin: *
   * Content-Type: application/json
   * {"template_code”:”test1","template_name":"test1","template_msg":"메시지내용","status": "등록","btnList": "template_btn_url2": null,"template_btn_name":"홈페이지",template_btn_url":"https://www.apistore.co.kr","template_btn_type”:”웹링크”}
   * 
   */

  public function getTemplate($params = array()) {
    // "템플릿조회"   => "https://api.apistore.co.kr/kko/1//template/list/" . $this->client_id,
    return( $this->get($this->apiUrls['템플릿조회'], $params) );
  }

  /*
   * 발신번호 등록
   * 
   * 요청 파라미터
   * Element    Type   Optional Description
   * sendnumber String No       발신번호(“-“ 제외) 발신번호 등록 규칙 참조
   * comment    String No       코멘트(200자)
   * pintype    String Yes      인증방법 (SMS. VMS 중 1개 선택)
   * pincode    String Yes      인증번호 (SMS 인증번호(6자리), VMS인증번호 (2자리))
   * 
   * 반환값
   * Element     Type   Optional Description
   * result_code String No       처리 결과 코드
   *                             200 : 성공
   *                             300 : 파라메터 에러
   *                             400 : 인증 업데이트 중 에러
   *                             500 : 이미 등록된 번호
   *                             600 : 일치 하지 않는 인증번호
   *                             700 : 핀코드 인증 시간 만료(3분 이후 만료이며 재등록 요청해야 함.)
   * sendnumber  String No       등록한 번호
   * 
   * 
   * 사용 예제
   * 
   * REQUEST
   * <요청>
   * URL http://api.apistore.co.kr/kko/2/sendnumber/save/{client_id}
   * POST
   * Content-Type: application/x-www-form-urlencoded; charset=UTF-8
   * Header
   * x-waple-authorization:MS0xMzY1NjY2MTAyNDk0LTA2MWE4ZDgyLTZhZmMtNGU5OS05YThkLTgyNmFmYzVlOTkzZQ==
   * Parameter
   * sendnumber=0232894122&comment=케이티하이텔대표번호&pintype=SMS 또는 VMS
   * <인증>
   * URL http://api.apistore.co.kr/kko/2/sendnumber/save/{client_id}
   * POST
   * Content-Type: application/x-www-form-urlencoded; charset=UTF-8
   * Header
   * x-waple-authorization:
   * MS0xMzY1NjY2MTAyNDk0LTA2MWE4ZDgyLTZhZmMtNGU5OS05YThkLTgyNmFmYzVlOTkzZQ==
   * Parameter
   * sendnumber=0232894122&comment=케이티하이텔대표번호&pintype=SMS&pincode=123456(6자리)
   * (pintype=VMS 일 경우 pincode=12(2자리))
   * 
   * RESPONSE
   * [요청]
   * 200
   * Access-Control-Allow-Origin: *
   * Content-Type: application/json
   * {"result_code":"200","sendnumber":"0232894122"}
   * 
   * [인증]
   * 200
   * Access-Control-Allow-Origin: *
   * Content-Type: application/json
   * {"result_code":"200","sendnumber":"0232894122"}
   * 
   */

  public function registSender($params) {
    // "발신번호등록" => "https://api.apistore.co.kr/kko/2/sendernumber/save/" . $this->client_id,

    if (!$params['sendnumber']) {
      throw new \Exception('발신번호가 비어있습니다.');
    }

    if (!$params['comment']) {
      throw new \Exception('코멘트가 비어있습니다.');
    }

    return( $this->post($this->apiUrls['발신번호등록'], $params) );
  }

  /*
   * 발신번호 목록 가져오기
   * 
   * 요청 파라미터
   * Element    Type   Optional Description
   * sendnumber String Yes      발신번호(“-“제외) – 입력 안 할경우 전체 리스트 반환
   * 
   * 반환값
   * Element     Type   Optional Description
   * result_code String No       처리 결과 코드
   *                             100 : User Error
   *                             200 : OK
   *                             300 : Parameter Error
   *                             400 : Etc Error
   *                             USE_YN : Y 값은 정상 등록된 번호. N 값은 요청 후 미인증 번호.
   * client_id   String No       API스토어 계정
   * comment     String No       등록 내용
   * sendnumber  String No       등록한 번호 
   * 
   * 
   * 사용 예제
   * 
   * REQUEST
   * URL http://api.apistore.co.kr/kko/1/sendnumber/list/{client_id}
   * GET
   * Content-Type: application/x-www-form-urlencoded; charset=UTF-8
   * Header
   * x-waple-authorization:MS0xMzY1NjY2MTAyNDk0LTA2MWE4ZDgyLTZhZmMtNGU5OS05YThkLTgyNmFmYzVlOTkzZQ==
   * 
   * RESONSE
   * 200
   * Access-Control-Allow-Origin: *
   * Content-Type: application/json
   * {"result_code":"200" , “numberList": [{“client_id” : “{client_id}” , “comment” : null ,“sendnumber":"0232892888"}, {“client_id” : “{client_id}” , “comment” : null ,“sendnumber":"0212345678"}
   * 
   */

  public function getSenders($params) {
    // "발신번호목록" => "https://api.apistore.co.kr/kko/1/sendernumber/list/" . $this->client_id,

    return( $this->get($this->apiUrls['발신번호목록'], $params) );
  }

  // API POST 호출
  public function post($url, $parameters = array()) {
    // 공통URL : http://api.apistore.co.kr/kko/{v1}/{type}/{client_id}
    // {client_id} 는 API스토어에 가입한 후 해당 API를 사용(구매) 신청한 ID.
    // { apiVersion } : 1
    // { type }: 은 msg, report 중 택1

    if (!$this->key) {
      throw new \Exception("API 키값이 비어있습니다.");
    }

    if (!$url) {
      throw new \Exception("URL 주소가 비어있습니다.");
    }

    if (!$apiVersion) {
      $apiVersion = 1;
    }


    \Unirest\Request::timeout($this->timeOut);
    $result = \Unirest\Request::post(
        $url, array(
        "x-waple-authorization" => $this->key
        ), $parameters
    );

    return( $result );
  }

  // API GET 호출
  public function get($url, $parameters = array()) {
    if (!$this->key) {
      throw new \Exception("API 키값이 비어있습니다.");
    }

    if (!$url) {
      throw new \Exception("URL 주소가 비어있습니다.");
    }

    \Unirest\Request::timeout($this->timeOut);
    $result = \Unirest\Request::get(
        $url, array(
        "x-waple-authorization" => $this->key
        ), $parameters
    );

    return( $result );
  }

}
