        <section class="reserve-form-panel">
            <div class="reserve-form-heading"><span class="gil-eyebrow">درخواست رزرو</span><h2>جزئیات درخواست</h2><p>موارد ستاره‌دار برای پیگیری درخواست ضروری‌اند. از رقم فارسی یا انگلیسی می‌توانی استفاده کنی.</p></div>
            <form method="POST" action="{{ route('public.reservations.store') }}" class="reserve-form" data-jalali-booking>
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ Illuminate\Support\Str::random(40) }}">
                <input type="hidden" name="starts_at" value="{{ old('starts_at') }}">
                <div class="reserve-honeypot" aria-hidden="true"><label>وب‌سایت <input name="website" tabindex="-1" autocomplete="off"></label></div>
                @if ($errors->any())
                    <div class="reserve-errors" role="alert"><b>درخواست ثبت نشد.</b><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <div class="reserve-form-grid">
                    <label><span>نوع درخواست <b>*</b></span><select name="purpose" required><option value="table" @selected(old('purpose','table')==='table')>رزرو میز</option><option value="birthday" @selected(old('purpose')==='birthday')>جشن تولد</option><option value="consultation" @selected(old('purpose')==='consultation')>وقت مشاوره</option><option value="private_event" @selected(old('purpose')==='private_event')>دورهمی خصوصی / مراسم</option><option value="catering" @selected(old('purpose')==='catering')>پذیرایی و کترینگ</option></select><small>برای ارجاع درست درخواست در پنل مدیریت.</small></label>
                    <label><span>نام و نام خانوادگی <b>*</b></span><input name="customer_name" value="{{ old('customer_name') }}" required maxlength="150" autocomplete="name"><small>نامی که برای پیگیری با آن تماس بگیریم.</small></label>
                    <label><span>شماره تماس <b>*</b></span><input name="phone" value="{{ old('phone') }}" required maxlength="30" autocomplete="tel" inputmode="tel"><small>برای تأیید زمان و هماهنگی جزئیات.</small></label>
                    <label><span>ایمیل (اختیاری)</span><input type="email" name="email" value="{{ old('email') }}" maxlength="150" autocomplete="email"></label>
                    <label class="reserve-datetime-field"><span>تاریخ و ساعت شمسی <b>*</b></span><input type="text" name="starts_at_jalali" value="{{ old('starts_at_jalali') }}" inputmode="numeric" autocomplete="off"  maxlength="16" required><small>قالب: سال/ماه/روز ساعت:دقیقه. زمان باید در آینده باشد و پس از بررسی ظرفیت تأیید می‌شود.</small></label>
                    <label><span>تعداد مهمان <b>*</b></span><input type="number" name="party_size" value="{{ old('party_size',2) }}" min="1" max="50" required><small>برای گروه بزرگ‌تر در توضیحات بنویس.</small></label>
                    <label><span>مدت تقریبی (دقیقه) <b>*</b></span><select name="duration_minutes" required><option value="60">۶۰ دقیقه</option><option value="90" selected>۹۰ دقیقه</option><option value="120">۱۲۰ دقیقه</option><option value="180">۱۸۰ دقیقه</option><option value="240">۲۴۰ دقیقه</option><option value="360">۳۶۰ دقیقه</option></select></label>
                    <label><span>نام مناسبت (اختیاری)</span><input name="occasion_name" value="{{ old('occasion_name') }}" maxlength="150" placeholder="مثلاً تولد سارا یا جلسه تیم"></label>
                    <label><span>بودجه حدودی (اختیاری، {{ $settings->currency_label }})</span><input type="number" name="estimated_budget" value="{{ old('estimated_budget') }}" min="0" max="1000000000000" step="10000"><small>برای پیشنهاد مناسب‌تر؛ تعهد قیمت یا پیش‌فاکتور نیست.</small></label>
                    <label class="reserve-notes-field"><span>توضیحات و نیازها (اختیاری)</span><textarea name="notes" rows="4" maxlength="1000" placeholder="مثلاً کیک، تزیین ساده، فضای آرام، محدودیت غذایی یا ساعت انعطاف‌پذیر">{{ old('notes') }}</textarea><small>هر جزئیاتی که به تصمیم‌گیری و هماهنگی کمک کند.</small></label>
                </div>
                <div class="reserve-form-footer"><p>درخواست ابتدا با وضعیت «در انتظار» ذخیره می‌شود و پس از بررسی، همکاران برای تأیید نهایی تماس می‌گیرند.</p><button type="submit" class="gil-button gil-button-dark">ارسال درخواست <span>↗</span></button></div>
            </form>
        </section>
