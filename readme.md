## Simple SMS Inject (Laravel)

This is a simple SMS Inject Object In Laravel, I am use [Gammu](https://wammu.eu/gammu/) as sms daemon, You can type this script to send sms,

Initialize Object Class

```
$inject = new SMSInjectController();

```

Send sms to one recipient

```
// send sms
$inject->send_sms($text_pesan, $msdn, $creator_id, $sender_id);

```

Send sms to multiple recipient / Blast SMS

```
// blast sms
$inject->mass_sms($text_pesan, $msdns, $creator_id, $sender_id); 

```
