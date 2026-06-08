    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         */
        public function up(): void
    {
        $settings = [
            ['key' => 'whatsapp_enabled',              'value' => '0',   'type' => 'boolean', 'group' => 'whatsapp', 'description' => 'Activar WhatsApp'],
            ['key' => 'whatsapp_token',                'value' => '',    'type' => 'string',  'group' => 'whatsapp', 'description' => 'Token de acceso Meta'],
            ['key' => 'whatsapp_phone_number_id',      'value' => '',    'type' => 'string',  'group' => 'whatsapp', 'description' => 'Phone Number ID'],
            ['key' => 'whatsapp_reminder_enabled',     'value' => '1',   'type' => 'boolean', 'group' => 'whatsapp', 'description' => 'Recordatorio de pago activo'],
            ['key' => 'whatsapp_reminder_time',        'value' => '09:00','type' => 'string', 'group' => 'whatsapp', 'description' => 'Hora de envío recordatorio'],
            ['key' => 'whatsapp_reminder_message',     'value' => "Hola {nombre} 👋\n\nTe recordamos que tu pago de *\${monto}* vence *mañana*.\n\nRealiza tu pago a tiempo para evitar cargos por mora.\n\n_{negocio}_", 'type' => 'text', 'group' => 'whatsapp', 'description' => 'Mensaje recordatorio'],
            ['key' => 'whatsapp_confirmation_enabled', 'value' => '1',   'type' => 'boolean', 'group' => 'whatsapp', 'description' => 'Confirmación de pago activa'],
            ['key' => 'whatsapp_confirmation_message', 'value' => "✅ *Pago registrado*\n\nHola {nombre}, confirmamos tu pago:\n\n• Monto: *\${monto}*\n• Fecha: *{fecha}*\n• Saldo restante: *\${saldo}*\n\n_{negocio}_", 'type' => 'text', 'group' => 'whatsapp', 'description' => 'Mensaje confirmación'],
            ['key' => 'whatsapp_overdue_enabled',      'value' => '1',   'type' => 'boolean', 'group' => 'whatsapp', 'description' => 'Alerta mora activa'],
            ['key' => 'whatsapp_overdue_message',      'value' => "⚠️ *Aviso de pago vencido*\n\nHola {nombre}, tu préstamo presenta un saldo vencido:\n\n• Mora acumulada: *\${mora}*\n• Saldo pendiente: *\${saldo}*\n\nComunícate con tu asesor para regularizar tu cuenta.\n\n_{negocio}_", 'type' => 'text', 'group' => 'whatsapp', 'description' => 'Mensaje mora'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('group', 'whatsapp')->delete();
    }
    };
