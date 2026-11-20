<?php
define('ABSPATH','/');
$GLOBALS['F']=[]; $GLOBALS['O']=['echwp_readonly_key_hash'=>'x']; $GLOBALS['M']=[]; $GLOBALS['U']=[]; $GLOBALS['AP']=[];
function add_filter($h,$c,$p=10,$a=1){$GLOBALS['F'][$h][]=$c;} function add_action($h,$c,$p=10,$a=1){$GLOBALS['F'][$h][]=$c;}
function apply($h,...$a){foreach($GLOBALS['F'][$h]??[] as $c){$a[0]=$c(...$a);}return $a[0];}
function get_option($k,$d=false){return $GLOBALS['O'][$k]??$d;} function update_option($k,$v,$al=null){$GLOBALS['O'][$k]=$v;return true;} function delete_option($k){unset($GLOBALS['O'][$k]);}
function get_user_meta($id,$k,$s){return $GLOBALS['M'][$id][$k]??'';} function update_user_meta($id,$k,$v){$GLOBALS['M'][$id][$k]=$v;}
class WP_Error{public $c,$m;function __construct($c='',$m=''){$this->c=$c;$this->m=$m;}function get_error_message(){return $this->m;}}
class WP_User{public $ID,$user_login,$roles=[];function __construct($id,$l,$r){$this->ID=$id;$this->user_login=$l;$this->roles=$r?[$r]:[];}function set_role($r){$this->roles=$r?[$r]:[];}}
function get_user_by($f,$v){foreach($GLOBALS['U'] as $u){if(($f==='login'&&$u->user_login===$v)||($f==='id'&&$u->ID===$v))return $u;}return false;}
function user_can($u,$c){return in_array('administrator',$u->roles,true);}
function wp_parse_url($u,$p){return parse_url($u,$p);} function home_url(){return 'https://darkpress.cz';} function get_rest_url(){return 'https://darkpress.cz/wp-json/';}
function wp_insert_user($a){$id=count($GLOBALS['U'])+10;$GLOBALS['U'][]=new WP_User($id,$a['user_login'],$a['role']);return $id;}
function is_wp_error($x){return $x instanceof WP_Error;} function wp_generate_password($n,$a=true,$b=false){return str_repeat('a',$n);}
function wp_is_application_passwords_available(){return true;} function wp_is_application_passwords_available_for_user($u){return true;}
class WP_Application_Passwords{static function get_user_application_passwords($id){return $GLOBALS['AP'][$id]??[];}
 static function delete_application_password($id,$uuid){$GLOBALS['AP'][$id]=array_values(array_filter($GLOBALS['AP'][$id],fn($i)=>$i['uuid']!==$uuid));return true;}
 static function create_new_application_password($id,$a){$i=['uuid'=>uniqid(),'app_id'=>$a['app_id'],'name'=>$a['name'],'created'=>time(),'last_used'=>null,'last_ip'=>null];$GLOBALS['AP'][$id][]=$i;return ['ABCDabcd1234EFGHefgh5678',$i];}}
function untrailingslashit($s){return rtrim($s,'/');} function wp_json_encode($v){return json_encode($v);}
function add_options_page(...$a){} function plugin_basename($f){return 'ec-connect-wordpress/ec-connect-wordpress.php';} function esc_url($s){return $s;} function admin_url($s){return '/wp-admin/'.$s;}
class O{public $_builtin=false,$show_in_rest=false,$public=true,$publicly_queryable=true,$labels;function __construct($n){$this->labels=(object)['name'=>$n];}}
function get_post_types($a,$o){return ['post'=>(function(){$x=new O('Příspěvky');$x->_builtin=true;$x->show_in_rest=true;return $x;})(),'ql_services'=>new O('Služby'),'wpcf7_contact_form'=>(function(){$x=new O('Formuláře');$x->public=false;$x->publicly_queryable=false;return $x;})()];}
function get_taxonomies($a,$o){return ['ql_cat'=>new O('Kategorie služeb')];}
function wp_roles(){return (object)['roles'=>['editor'=>['name'=>'Editor'],'author'=>['name'=>'Author'],'contributor'=>['name'=>'Contributor']]];}
function translate_user_role($n){return $n;} function current_user_can($c){return true;} function wp_die($m){throw new Exception($m);}
function check_admin_referer($a){return true;} function sanitize_key($k){return strtolower(preg_replace('/[^a-z0-9_\-]/i','',$k));} function wp_unslash($v){return $v;}
function esc_html($s){return htmlspecialchars((string)$s);} function esc_textarea($s){return htmlspecialchars($s);} function esc_attr($s){return htmlspecialchars((string)$s);} function esc_js($s){return addslashes($s);}
function selected($a,$b,$e){return $a===$b?' selected':'';} function checked($a,$b,$e){return $a===$b?' checked':'';} function wp_nonce_field($a){echo '<nonce>';} function wp_date($f,$t){return date($f,$t);}
$GLOBALS['RF']=[]; function register_rest_field($o,$f,$a){$GLOBALS['RF'][$o.'.'.$f]=$a;} $GLOBALS['CUR']=0; function get_current_user_id(){return $GLOBALS['CUR'];} function get_userdata($id){foreach($GLOBALS['U'] as $u)if($u->ID===$id){$u->allcaps=[];return $u;}return false;}
class RR{public $h=[];function header($k,$v){$this->h[$k]=$v;}}
$GLOBALS['RM']=[]; function registered_meta_key_exists($t,$k,$s){return isset($GLOBALS['RM'][$k]);} function register_post_meta($pt,$k,$a){$GLOBALS['RM'][$k]=$a+['pt'=>$pt];return true;}
function sanitize_text_field($s){return trim(strip_tags($s));} function sanitize_textarea_field($s){return trim(strip_tags($s));}
class FakeWpdb{public $postmeta='wp_postmeta',$posts='wp_posts',$termmeta='wp_termmeta',$term_taxonomy='wp_term_taxonomy';public $q=0;
 function get_col($sql){$this->q++; if(str_contains($sql,"meta_key = 'author'"))return str_contains($sql,'termmeta')?['3']:['1','1','x'];
  return str_contains($sql,'termmeta')?['stec_logged_in','stec_private']:['stec_public','stec_private','editor',"bad value"];}}
$GLOBALS['wpdb']=new FakeWpdb();
// 0.6.0 updater stubs
define('HOUR_IN_SECONDS',3600); $GLOBALS['T']=[]; $GLOBALS['NET']=[]; $GLOBALS['DL']=null;
function get_site_transient($k){return $GLOBALS['T'][$k]['v']??false;} function set_site_transient($k,$v,$t){$GLOBALS['T'][$k]=['v'=>$v,'t'=>$t];return true;} function delete_site_transient($k){unset($GLOBALS['T'][$k]);}
function wp_remote_get($u,$a){$GLOBALS['NET'][]=[$u,$a];return $GLOBALS['REMOTE'];} function wp_remote_retrieve_response_code($r){return $r['code'];} function wp_remote_retrieve_body($r){return $r['body'];}
function download_url($u,$t){$f=tempnam(sys_get_temp_dir(),'echc');file_put_contents($f,$GLOBALS['DL']);return $f;} function wp_delete_file($f){@unlink($f);}
require $argv[1];
$ok=function($c,$m){echo ($c?'PASS ':'FAIL ').$m."\n";};
// 0.3.0 Yoast meta: nothing without Yoast, then registered for all types with edit_post check
foreach($GLOBALS['F']['init'] as $c)$c(); $ok($GLOBALS['RM']===[],'bez Yoastu se SEO pole neregistruji');
define('WPSEO_VERSION','28.8'); foreach($GLOBALS['F']['init'] as $c)$c();
$m=$GLOBALS['RM']['_yoast_wpseo_metadesc']??null; $ok(count($GLOBALS['RM'])===3&&$m&&$m['pt']===''&&$m['show_in_rest']['schema']['context']===['edit']&&$m['single']===true,'Yoast: 3 pole pro vsechny typy v REST, jen kontext uprav');
$ok(($m['sanitize_callback'])('<b>Popis</b> ')==='Popis','Yoast: sanitace');
// 0.4.0 Stachethemes adapter: off without STEC, off until enabled, only for ec-hub, version-gated
$ok(apply('manage_stec_event',0,10)===0&&echc_stec_version()==='','bez Stachethemes beze zmeny');
// admin_init cleanup
foreach($GLOBALS['F']['admin_init'] as $c)$c(); $ok(!isset($GLOBALS['O']['echwp_readonly_key_hash']),'stara volba 0.1.0 smazana');
// connect
$code=echc_connect('editor'); $ok(is_string($code)&&str_starts_with($code,'ECHUB-WP1.'),'parovaci kod');
$p=json_decode(base64_decode(strtr(substr($code,10),'-_','+/')),true); $ok($p['site']==='https://darkpress.cz'&&$p['user']==='ec-hub'&&$p['password']==='ABCDabcd1234EFGHefgh5678'&&$p['rest']==='https://darkpress.cz/wp-json/','obsah kodu');
$u=echc_user(); $ok($u&&$u->roles===['editor'],'uzivatel ec-hub editor + oznaceny');
$code2=echc_connect('author'); $ok(count($GLOBALS['AP'][$u->ID])===1&&$u->roles===['author'],'novy kod = jen jedno heslo, role author');
$ok(is_wp_error(echc_connect('administrator')),'role administrator odmitnuta');
// login blocked, app password unaffected
$r=apply('wp_authenticate_user',$u,'x'); $ok(is_wp_error($r),'prihlaseni formularem blokovano');
$other=new WP_User(99,'jan','editor'); $ok(apply('wp_authenticate_user',$other,'x')===$other,'jiny uzivatel neovlivnen');
$ok(apply('allow_password_reset',true,$u->ID)===false,'reset hesla blokovan');
// rest exposure
$_SERVER['REQUEST_METHOD']='POST'; $_POST=['echc_action'=>'rest','echc_types'=>['ql_services','post','evil'],'echc_taxonomies'=>['ql_cat']];
ob_start(); echc_page(); $html=ob_get_clean();
$ok(get_option(ECHC_OPT_TYPES)===['ql_services'],'ulozen jen skryty typ (post/evil zahozeny)');
$args=apply('register_post_type_args',['show_in_rest'=>false,'public'=>true],'ql_services'); $ok($args['show_in_rest']===true,'show_in_rest zapnuto pro ql_services');
$GLOBALS['O'][ECHC_OPT_TYPES][]='wpcf7_contact_form'; $args=apply('register_post_type_args',['show_in_rest'=>false,'public'=>false],'wpcf7_contact_form'); $ok($args['show_in_rest']===false,'neverejny typ se nezpristupni ani kdyz je ulozeny');
$ok(!isset(echc_hidden_objects('types')['wpcf7_contact_form'])&&isset(echc_hidden_objects('types')['ql_services']),'nabidka jen verejnych typu');
$args=apply('register_post_type_args',['show_in_rest'=>false],'jiny'); $ok($args['show_in_rest']===false,'jiny typ beze zmeny');
$ok(apply('use_block_editor_for_post_type',true,'ql_services')===false,'blokovy editor vypnut pro ql_services');
$ok(apply('register_taxonomy_args',['show_in_rest'=>false,'public'=>true],'ql_cat')['show_in_rest']===true,'taxonomie zpristupnena');
// page states
$_POST=[];$_SERVER['REQUEST_METHOD']='GET'; ob_start(); echc_page(); $html=ob_get_clean(); $ok(str_contains($html,'Připojeno')&&str_contains($html,'Služby'),'stranka: pripojeno + seznam typu');
$_SERVER['REQUEST_METHOD']='POST'; $_POST=['echc_action'=>'connect','echc_role'=>'editor']; ob_start(); echc_page(); $html=ob_get_clean(); $ok(str_contains($html,'ECHUB-WP1.'),'stranka zobrazi kod po pripojeni');
$_POST=['echc_action'=>'disconnect']; ob_start(); echc_page(); $html=ob_get_clean(); $ok(empty($GLOBALS['AP'][$u->ID])&&$u->roles===[]&&str_contains($html,'Připojit k EC Hubu'),'odpojeni: heslo zruseno, bez role, nabidne pripojeni');
// existing admin named ec-hub not adopted
$GLOBALS['U']=[new WP_User(5,'ec-hub','administrator')];$GLOBALS['M']=[]; $ok(is_wp_error(echc_connect('editor')),'existujici spravce ec-hub neprevzat');

// --- 0.4.0 Stachethemes adapter with STEC present
$GLOBALS['U']=[];$GLOBALS['M']=[];$GLOBALS['O']=[];
$c=echc_connect('editor'); $hub=echc_user();
eval('namespace Stachethemes\\Stec; class Permissions{}');
define('STEC_PLUGIN_VERSION','5.5.9');
$ok(echc_stec_supported(),'stec 5.5.9 podporovan');
$ok(apply('manage_stec_event',0,$hub->ID)===0,'vypnuto: ec-hub nema manage_stec_event');
$_SERVER['REQUEST_METHOD']='POST'; $_POST=['echc_action'=>'stec','echc_stec'=>'1']; ob_start(); echc_page(); $html=ob_get_clean();
$ok(get_option(ECHC_OPT_STEC)==='1'&&str_contains($html,'smí spravovat kalendář'),'zapnuti spravy kalendare');
$ok(apply('manage_stec_event',0,$hub->ID)===1&&apply('manage_stec_loc',0,$hub->ID)===1,'ec-hub ma manage_stec_event a manage_stec_loc');
$ok(apply('manage_stec_event',0,99)===0,'jiny uzivatel beze zmeny');
$l=apply('stec_user_permissions_list',['editor','stec_public','stec_logged_in','stec_user_'.$hub->ID],$hub->ID);
$ok(in_array('stec_user_1',$l,true)&&in_array('stec_user_3',$l,true)&&!in_array('stec_private',$l,true)&&!in_array('bad value',$l,true)&&!in_array('stec_user_0',$l,true),'tokeny: autori akci a kalendaru, bez stec_private a nevalidnich');
$q=$GLOBALS['wpdb']->q; apply('stec_user_permissions_list',[],$hub->ID); $ok($GLOBALS['wpdb']->q===$q,'tokeny jen jednou za pozadavek');
$ok(apply('stec_user_permissions_list',['x'],99)===['x'],'jiny uzivatel: seznam beze zmeny');
$_POST=['echc_action'=>'stec']; ob_start(); echc_page(); ob_get_clean();
$ok(get_option(ECHC_OPT_STEC)==='0'&&apply('manage_stec_event',0,$hub->ID)===0,'vypnuti hned odebere prava');

// --- 0.5.0 cap guard and REST field
$hub=echc_user(); $other=new WP_User(99,'admin',['administrator']);
$caps=['edit_posts'=>true,'unfiltered_html'=>true,'manage_options'=>true,'install_plugins'=>true,'promote_users'=>true,'edit_others_posts'=>true];
$r=apply('user_has_cap',$caps,['manage_options'],[],$hub);
$ok($r['manage_options']===false&&$r['install_plugins']===false&&$r['promote_users']===false&&$r['unfiltered_html']===false&&$r['edit_posts']===true&&$r['edit_others_posts']===true,'ec-hub: zadna sprava webu, bez unfiltered_html, obsah ano');
$ok(apply('user_has_cap',$caps,['manage_options'],[],$other)===$caps,'jiny uzivatel beze zmeny');
$_SERVER['REQUEST_METHOD']='POST'; $_POST=['echc_action'=>'unfiltered','echc_unfiltered'=>'1']; ob_start(); echc_page(); $html=ob_get_clean();
$r=apply('user_has_cap',$caps,['unfiltered_html'],[],$hub); $ok($r['unfiltered_html']===true&&$r['manage_options']===false&&str_contains($html,'pozná hned'),'volba: nefiltrovane HTML povoleno, sprava webu stale ne');
foreach($GLOBALS['F']['rest_api_init'] as $c)$c(); $f=$GLOBALS['RF']['user.ec_hub_connect']??null;
$GLOBALS['CUR']=$hub->ID; $v=($f['get_callback'])(['id'=>$hub->ID]);
$ok($f['schema']['context']===['edit']&&$v['version']===ECHC_VERSION&&$v['unfiltered_html']===true,'REST pole ec_hub_connect pro ec-hub');
$ok(($f['get_callback'])(['id'=>99])===null,'REST pole pro jineho uzivatele prazdne');
$_POST=['echc_action'=>'unfiltered']; ob_start(); echc_page(); ob_get_clean(); $ok(!echc_unfiltered_allowed(),'vypnuti nefiltrovaneho HTML');

$GLOBALS['CUR']=$hub->ID; $rr=apply('rest_post_dispatch',new RR()); $ok(($rr->h['X-EC-Hub-Connect']??'')==='v='.ECHC_VERSION.';unfiltered=0;calendar=0;admin=0','hlavicka s nastavenim pro ec-hub');
$GLOBALS['CUR']=99; $rr=apply('rest_post_dispatch',new RR()); $ok($rr->h===[],'jiny uzivatel bez hlavicky');

// --- 0.6.0 updates from GitHub (Update URI + checksum)
$repo='https://github.com/Euroclean/ec-connect-wordpress'; $zip='PK-zip-0.6.1'; $sha=hash('sha256',$zip);
$good=['version'=>'0.6.1','package'=>$repo.'/releases/download/v0.6.1/ec-connect-wordpress-0.6.1.zip','sha256'=>$sha,'requires'=>'6.0','requires_php'=>'7.4','tested'=>'6.9'];
$GLOBALS['REMOTE']=['code'=>200,'body'=>json_encode($good)];
$u=apply('update_plugins_github.com',false,[],'ec-connect-wordpress/ec-connect-wordpress.php');
$ok(is_array($u)&&$u['version']==='0.6.1'&&$u['package']===$good['package']&&$u['slug']==='ec-connect-wordpress','nova verze nabidnuta WordPressu');
$ok($GLOBALS['NET'][0][0]==='https://raw.githubusercontent.com/Euroclean/ec-connect-wordpress/main/update.json'&&$GLOBALS['NET'][0][1]['redirection']===0,'update.json z pevne adresy bez presmerovani');
apply('update_plugins_github.com',false,[],'ec-connect-wordpress/ec-connect-wordpress.php'); $ok(count($GLOBALS['NET'])===1&&$GLOBALS['T']['ec_hub_connect_update']['t']===43200,'vysledek ulozen na 12 hodin');
$ok(apply('update_plugins_github.com',false,[],'jiny/plugin.php')===false,'jiny plugin beze zmeny');
foreach([['package'=>'https://evil.example/x.zip'],['package'=>$repo.'/releases/download/v0.6.2/ec-connect-wordpress-0.6.2.zip'],['sha256'=>'abc'],['version'=>'0.6.1-beta']] as $bad){$ok(echc_valid_update(array_merge($good,$bad))===null,'odmitnuto: '.json_encode($bad));}
$GLOBALS['DL']=$zip; $f=apply('upgrader_pre_download',false,$good['package']); $ok(is_string($f)&&file_get_contents($f)===$zip,'balicek se spravnym SHA-256 prijat'); @unlink($f);
$GLOBALS['DL']='podvrzeny'; $r=apply('upgrader_pre_download',false,$good['package']); $ok(is_wp_error($r)&&$r->c==='echc_update_checksum','podvrzeny balicek odmitnut');
$r=apply('upgrader_pre_download',false,$repo.'/releases/download/v0.6.0/ec-connect-wordpress-0.6.0.zip'); $ok(is_wp_error($r)&&$r->c==='echc_update_unknown','jiny balicek nez zverejneny odmitnut');
$ok(apply('upgrader_pre_download',false,'https://downloads.wordpress.org/plugin/x.zip')===false,'cizi pluginy beze zmeny');
$GLOBALS['T']=[];$GLOBALS['REMOTE']=['code'=>404,'body'=>'']; $ok(apply('update_plugins_github.com',false,[],'ec-connect-wordpress/ec-connect-wordpress.php')===false&&$GLOBALS['T']['ec_hub_connect_update']['t']===3600,'chyba GitHubu: zadna nabidka, dalsi pokus za hodinu');
