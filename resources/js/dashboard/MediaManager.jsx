import React, { useEffect, useState } from 'react';
import { api, unwrapList } from './api.js';

const slots = [
    { key: 'hero', title: 'تصویر اصلی صفحه خانه', detail: 'بنر بزرگ ابتدای سایت؛ تصویر افقی و باکیفیت انتخاب کن.', ratio: 'حداقل ۱۹۲۰×۱۰۰۰', aspect: 'wide' },
    { key: 'login_background', title: 'پس‌زمینه ورود کارکنان', detail: 'تصویر پشت فرم ورود مدیر و کارکنان.', ratio: 'حداقل ۱۶۰۰×۱۲۰۰', aspect: 'wide' },
    { key: 'menu_cover', title: 'کاور صفحه منو', detail: 'تصویر بالای فهرست محصولات و دسته‌بندی‌ها.', ratio: 'حداقل ۱۶۰۰×۹۰۰', aspect: 'wide' },
    { key: 'brand_logo', title: 'لوگوی مجموعه', detail: 'ترجیحاً PNG یا WebP با پس‌زمینه شفاف.', ratio: 'مربع، ۵۱۲×۵۱۲', aspect: 'square' },
    { key: 'story_main', title: 'تصویر داستان ما', detail: 'تصویر اصلی بخش معرفی و داستان خانه گیلاسی.', ratio: 'حداقل ۱۲۰۰×۱۴۰۰', aspect: 'portrait' },
    { key: 'story_small', title: 'تصویر دوم داستان', detail: 'تصویر مکمل برای بخش معرفی کافه.', ratio: 'حداقل ۹۰۰×۱۱۰۰', aspect: 'portrait' },
    { key: 'gallery_1', title: 'گالری — فضای کافه', detail: 'نمای داخلی، میزها و فضای پذیرایی.', ratio: 'حداقل ۱۲۰۰×۹۰۰', aspect: 'wide' },
    { key: 'gallery_2', title: 'گالری — غذا و نوشیدنی', detail: 'تصویر منتخب از غذاها یا نوشیدنی‌ها.', ratio: 'حداقل ۱۲۰۰×۹۰۰', aspect: 'wide' },
    { key: 'gallery_3', title: 'گالری — مناسبت‌ها', detail: 'تصویر چیدمان میز، تولد و دورهمی‌ها.', ratio: 'حداقل ۱۲۰۰×۹۰۰', aspect: 'wide' },
];

const ActionButton = ({ children, kind = 'secondary', disabled = false, onClick }) => (
    <button type="button" className={'media-action-button is-' + kind} disabled={disabled} onClick={onClick}>{children}</button>
);

export default function MediaManager({ onNavigate }) {
    const [assets, setAssets] = useState([]);
    const [files, setFiles] = useState({});
    const [busy, setBusy] = useState('');
    const [error, setError] = useState('');
    const [notice, setNotice] = useState('');
    const [version, setVersion] = useState(0);

    const load = async () => {
        setBusy('load');
        setError('');
        try {
            const payload = await api('/media');
            setAssets(unwrapList(payload));
        } catch (e) {
            setError(e.message || 'بارگذاری کتابخانه تصاویر انجام نشد.');
        } finally {
            setBusy('');
        }
    };

    useEffect(() => { load(); }, [version]);

    const upload = async slot => {
        const file = files[slot];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            setError('فقط فایل JPG، PNG یا WebP قابل بارگذاری است.');
            return;
        }
        if (file.size > 8 * 1024 * 1024) {
            setError('حجم تصویر نباید بیشتر از ۸ مگابایت باشد.');
            return;
        }
        const form = new FormData();
        form.append('slot', slot);
        form.append('image', file);
        form.append('alt_text', slots.find(item => item.key === slot)?.title || '');
        setBusy(slot);
        setError('');
        setNotice('');
        try {
            await api('/media', { method: 'POST', body: form });
            setFiles(previous => ({ ...previous, [slot]: null }));
            setNotice('تصویر با موفقیت جایگزین شد.');
            setVersion(v => v + 1);
        } catch (e) {
            setError(e.message || 'بارگذاری تصویر ناموفق بود.');
        } finally {
            setBusy('');
        }
    };

    const remove = async asset => {
        if (!window.confirm('این تصویر از سایت حذف شود؟')) return;
        setBusy(asset.slot);
        setError('');
        try {
            await api('/media/' + asset.id, { method: 'DELETE' });
            setNotice('تصویر حذف شد؛ در صورت وجود، تصویر پیش‌فرض نمایش داده می‌شود.');
            setVersion(v => v + 1);
        } catch (e) {
            setError(e.message || 'حذف تصویر انجام نشد.');
        } finally {
            setBusy('');
        }
    };

    return <div className="page-content media-manager">
        <header className="media-page-header">
            <span className="media-page-eyebrow">کتابخانه بصری خانه گیلاسی</span>
            <h1>رسانه و تصاویر سایت</h1>
            <p>تصاویر صفحه خانه، منو، ورود و گالری را از همین‌جا مدیریت کن. عکس هر محصول هم از بخش «منو و محصولات» قابل بارگذاری است.</p>
        </header>
        <div className="media-manager-intro">
            <span className="media-manager-intro-icon">✦</span>
            <div><b>کتابخانه تصویر خانه گیلاسی</b><p>فایل‌ها در فضای عمومی تصاویر سایت ذخیره می‌شوند. با بارگذاری تصویر جدید، فقط همان جایگاه عوض می‌شود و اطلاعات سفارش‌ها و محصولات دست‌نخورده می‌ماند.</p></div>
            <ActionButton disabled={busy !== ''} onClick={() => setVersion(v => v + 1)}>↻ بازخوانی</ActionButton>
        </div>
        {error && <div className="media-manager-message is-error" role="alert">{error}</div>}
        {notice && <div className="media-manager-message is-success" role="status">{notice}</div>}
        <div className="media-manager-grid">
            {slots.map(slot => {
                const asset = assets.find(item => item.slot === slot.key);
                const file = files[slot.key];
                return <article className="media-asset-card" key={slot.key}>
                    <div className={'media-asset-preview is-' + slot.aspect}>
                        {asset?.url
                            ? <img src={asset.url} alt={asset.alt_text || slot.title} />
                            : <div className="media-asset-empty"><span>＋</span><b>هنوز تصویری ثبت نشده</b><small>تصویر پیش‌فرض سایت نمایش داده می‌شود</small></div>}
                        <span className={'media-asset-status ' + (asset ? 'is-uploaded' : 'is-default')}>{asset ? 'تصویر سفارشی' : 'تصویر پیش‌فرض'}</span>
                    </div>
                    <div className="media-asset-body">
                        <div className="media-asset-heading"><div><h3>{slot.title}</h3><p>{slot.detail}</p></div><code>{slot.key}</code></div>
                        <div className="media-asset-guidance">پیشنهاد اندازه: {slot.ratio} · JPG / PNG / WebP · حداکثر ۸ مگابایت</div>
                        <label className="media-file-picker"><span>انتخاب تصویر از کامپیوتر</span><input type="file" accept="image/jpeg,image/png,image/webp" onChange={e => setFiles(previous => ({ ...previous, [slot.key]: e.target.files?.[0] || null }))} /></label>
                        {file && <div className="media-selected-file"><span>{file.name}</span><b>{(file.size / 1024 / 1024).toFixed(2)} MB</b></div>}
                        <div className="media-asset-actions">
                            <ActionButton kind="primary" disabled={!file || busy !== ''} onClick={() => upload(slot.key)}>{busy === slot.key ? 'در حال بارگذاری…' : asset ? 'جایگزینی تصویر' : 'بارگذاری تصویر'}</ActionButton>
                            {asset && <ActionButton kind="danger" disabled={busy !== ''} onClick={() => remove(asset)}>حذف تصویر</ActionButton>}
                        </div>
                        {asset?.updated_at && <small className="media-asset-updated">آخرین تغییر: {new Intl.DateTimeFormat('fa-IR-u-ca-persian', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(asset.updated_at))}</small>}
                    </div>
                </article>;
            })}
        </div>
        <section className="media-product-note">
            <div><b>تصاویر محصولات و دسته‌بندی‌ها</b><p>برای عکس هر غذا یا نوشیدنی، وارد «منو و محصولات ← ویرایش محصول» شو. تصویر دسته‌بندی از اولین محصول فعال همان دسته گرفته می‌شود.</p></div>
            <ActionButton onClick={() => onNavigate?.('menu')}>رفتن به منو و محصولات</ActionButton>
        </section>
    </div>;
}
