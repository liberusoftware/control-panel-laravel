<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Mail\Models;
use Illuminate\Database\Eloquent\Model;
final class MailControl extends Model { protected $table='control_panel_mail_controls'; protected $fillable=['id','team_id','mail_account_id','spam_filter_enabled','spam_threshold','spam_action','virus_scan_enabled','autoresponder_enabled','autoresponder_subject','autoresponder_message','autoresponder_start_at','autoresponder_end_at','keep_copy_on_server']; protected function casts():array{return ['spam_filter_enabled'=>'boolean','spam_threshold'=>'integer','spam_action'=>'string','virus_scan_enabled'=>'boolean','autoresponder_enabled'=>'boolean','autoresponder_start_at'=>'datetime','autoresponder_end_at'=>'datetime','keep_copy_on_server'=>'boolean'];} }
