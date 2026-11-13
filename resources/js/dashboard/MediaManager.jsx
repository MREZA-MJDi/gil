import React, { useEffect, useRef, useState } from 'react';
import { api, unwrapList } from './api.js';

const slots = [
    { key: 'hero', title: 'بنر اصلی صفحه خانه', detail: 'عکس اصلی هیرو در بالای صفحه خانه.', ratio: '۱۶:۹', aspect: '16 / 9', crop: 16/9, className: 'wide' },
    { key: 'home_showcase_background', title: 'پس‌زمینه بخش محصولات منتخب', detail: 'تصویر پس‌زمینه بخش تیره اسلایدر محصولات منتخب.', ratio: '۱۶:۹', aspect: '16 / 9', crop: 16/9, className: 'wide' },
    { key: 'home_reservation_banner', title: 'بنر دعوت به رزرو در صفحه خانه', detail: 'تصویر پس‌زمینه بنر بزرگ رزرو و مناسبت‌ها.', ratio: '۲۱:۹', aspect: '21 / 9', crop: 21/9, className: 'wide' },
    { key: 'menu_cover', title: 'کاور صفحه منو', detail: 'تصویر افقی بالای صفحه منوی عمومی.', ratio: '۱۶:۵', aspect: '16 / 5', crop: 16/5, className: 'wide' },
    { key: 'reservation_cover', title: 'تصویر صفحه رزرو', detail: 'تصویر اصلی کنار فرم رزرو میز و مناسبت‌ها.', ratio: '۴:۵', aspect: '4 / 5', crop: 4/5, className: 'portrait' },
    { key: 'login_background', title: 'پس‌زمینه ورود کارکنان', detail: 'تصویر پشت فرم ورود مدیر و کارکنان.', ratio: '۱۶:۹', aspect: '16 / 9', crop: 16/9, className: 'wide' },
    { key: 'brand_logo', title: 'لوگوی مجموعه', detail: 'برای نمایش لوگو در سربرگ و پایین صفحه؛ PNG شفاف مناسب است.', ratio: '۱:۱', aspect: '1 / 1', crop: 1, className: 'square' },
    { key: 'story_main', title: 'تصویر داستان ما', detail: 'تصویر اصلی بخش معرفی خانه گیلاسی.', ratio: '۴:۵', aspect: '4 / 5', crop: 4/5, className: 'portrait' },
    { key: 'story_small', title: 'تصویر دوم داستان / رزرو', detail: 'تصویر مکمل برای معرفی مجموعه و جایگزین تصویر رزرو.', ratio: '۴:۵', aspect: '4 / 5', crop: 4/5, className: 'portrait' },
    { key: 'gallery_1', title: 'گالری — فضای کافه', detail: 'عکس فضای داخلی، میزها و پذیرایی.', ratio: '۴:۳', aspect: '4 / 3', crop: 4/3, className: 'wide' },
    { key: 'gallery_2', title: 'گالری — غذا و نوشیدنی', detail: 'عکس غذا، نوشیدنی یا نمای نزدیک محصولات.', ratio: '۴:۳', aspect: '4 / 3', crop: 4/3, className: 'wide' },
    { key: 'gallery_3', title: 'گالری — مناسبت‌ها', detail: 'عکس چیدمان میز، تولد و دورهمی‌ها.', ratio: '۴:۳', aspect: '4 / 3', crop: 4/3, className: 'wide' },
];

const ActionButton = ({ children, kind = 'secondary', disabled = false, onClick }) => (
    <button type="button" className={'media-action-button is-' + kind} disabled={disabled} onClick={onClick}>{children}</button>
);

const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

function loadImage(url) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error('بازکردن تصویر انتخاب‌شده ناموفق بود.'));
        image.src = url;
    });
}

async function cropImage(file, aspect, zoom, offsetX, offsetY) {
    const sourceUrl = trackUrl(URL.createObjectURL(file));
    try {
        const image = await loadImage(sourceUrl);
        const baseWidth = Math.min(image.naturalWidth, image.naturalHeight * aspect);
        const baseHeight = baseWidth / aspect;
        const sourceWidth = baseWidth / zoom;
        const sourceHeight = baseHeight / zoom;
        const availableX = Math.max(0, image.naturalWidth - sourceWidth);
        const availableY = Math.max(0, image.naturalHeight - sourceHeight);
        const sx = clamp((image.naturalWidth - sourceWidth) / 2 + offsetX * availableX / 2, 0, availableX);
        const sy = clamp((image.naturalHeight - sourceHeight) / 2 + offsetY * availableY / 2, 0, availableY);
        const targetWidth = Math.min(2000, Math.max(800, Math.round(sourceWidth)));
        const targetHeight = Math.round(targetWidth / aspect);
        const canvas = document.createElement('canvas');
        canvas.width = targetWidth;
        canvas.height = targetHeight;
        const context = canvas.getContext('2d');
        if (!context) throw new Error('ساخت پیش‌نمایش برش تصویر در مرورگر پشتیبانی نمی‌شود.');
        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = 'high';
        context.drawImage(image, sx, sy, sourceWidth, sourceHeight, 0, 0, targetWidth, targetHeight);

        const outputType = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
        const blob = await new Promise((resolve, reject) => {
            canvas.toBlob(result => result ? resolve(result) : reject(new Error('خروجی تصویر ساخته نشد.')), outputType, 0.92);
        });
        const extension = outputType === 'image/png' ? 'png' : 'jpg';
        const filename = file.name.replace(/\.[^.]+$/, '') + '-cropped.' + extension;
        return new File([blob], filename, { type: outputType, lastModified: Date.now() });
    } finally {
        URL.revokeObjectURL(sourceUrl);
    }
}

function CropDialog({ slot, file, sourceUrl, onCancel, onApply }) {
    const [zoom, setZoom] = useState(1);
    const [offset, setOffset] = useState({ x: 0, y: 0 });
    const [drag, setDrag] = useState(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');

    const startDrag = event => {
        event.currentTarget.setPointerCapture?.(event.pointerId);
        setDrag({ x: event.clientX, y: event.clientY, offset });
    };
    const moveDrag = event => {
        if (!drag) return;
        const rect = event.currentTarget.getBoundingClientRect();
        setOffset({
            x: clamp(drag.offset.x + ((event.clientX - drag.x) / Math.max(1, rect.width)) * 2.2, -1, 1),
            y: clamp(drag.offset.y + ((event.clientY - drag.y) / Math.max(1, rect.height)) * 2.2, -1, 1),
        });
    };
    const apply = async () => {
        setBusy(true);
        setError('');
        try {
            const result = await cropImage(file, slot.crop, zoom, offset.x, offset.y);
            onApply(result);
        } catch (exception) {
            setError(exception?.message || 'برش تصویر انجام نشد.');
        } finally {
            setBusy(false);
        }
    };

    return <div className="media-crop-backdrop" role="presentation" onMouseDown={event => { if (event.target === event.currentTarget && !busy) onCancel(); }}>
        <section className="media-crop-dialog" role="dialog" aria-modal="true" aria-label={'برش تصویر ' + slot.title}>
            <header className="media-crop-header">
                <div><span>آماده‌سازی تصویر</span><h2>{slot.title}</h2><small>کادر را با کشیدن عکس جابه‌جا کن و زوم را تنظیم کن.</small></div>
                <button type="button" onClick={onCancel} disabled={busy} aria-label="بستن">×</button>
            </header>
            <div className="media-crop-stage-wrap">
                <div
                    className={'media-crop-stage is-' + slot.className}
                    style={{ aspectRatio: slot.aspect }}
                    onPointerDown={startDrag}
                    onPointerMove={moveDrag}
                    onPointerUp={() => setDrag(null)}
                    onPointerCancel={() => setDrag(null)}
                >
                    <img src={sourceUrl} alt="پیش‌نمایش برش تصویر" draggable="false"
                        style={{ transform: 'translate(' + (offset.x * 18) + '%,' + (offset.y * 18) + '%) scale(' + zoom + ')' }} />
                    <span className="media-crop-guide" aria-hidden="true"></span>
                </div>
            </div>
            <div className="media-crop-controls">
                <label><span>بزرگ‌نمایی</span><input type="range" min="1" max="3" step="0.05" value={zoom} onChange={event => setZoom(Number(event.target.value))} /></label>
                <output>{Math.round(zoom * 100)}٪</output>
                <button type="button" onClick={() => { setZoom(1); setOffset({ x: 0, y: 0 }); }}>بازنشانی کادر</button>
            </div>
            {error && <p className="media-manager-message is-error" role="alert">{error}</p>}
            <footer className="media-crop-actions">
                <span>خروجی: {slot.ratio} · تصویر نهایی پیش از ذخیره قابل بازبینی است.</span>
                <div><ActionButton disabled={busy} onClick={onCancel}>انصراف</ActionButton><ActionButton kind="primary" disabled={busy} onClick={apply}>{busy ? 'در حال ساخت پیش‌نمایش…' : 'اعمال برش و ادامه'}</ActionButton></div>
            </footer>
        </section>
    </div>;
}

export default function MediaManager({ onNavigate }) {
    const [assets, setAssets] = useState([]);
    const [files, setFiles] = useState({});
    const [cropCandidate, setCropCandidate] = useState(null);
    const [busy, setBusy] = useState('');
    const [error, setError] = useState('');
    const [notice, setNotice] = useState('');
    const [version, setVersion] = useState(0);
    const objectUrls = useRef(new Set());

    const trackUrl = url => {
        objectUrls.current.add(url);
        return url;
    };
    const revokeUrl = url => {
        if (!url) return;
        URL.revokeObjectURL(url);
        objectUrls.current.delete(url);
    };

    useEffect(() => () => {
        objectUrls.current.forEach(url => URL.revokeObjectURL(url));
        objectUrls.current.clear();
    }, []);

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

    const chooseFile = (slot, file) => {
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            setError('فقط فایل JPG، PNG یا WebP قابل بارگذاری است.');
            return;
        }
        if (file.size > 8 * 1024 * 1024) {
            setError('حجم تصویر نباید بیشتر از ۸ مگابایت باشد.');
            return;
        }
        const sourceUrl = URL.createObjectURL(file);
        setCropCandidate({ slot, file, sourceUrl });
        setError('');
        setNotice('');
    };

    const applyCrop = async croppedFile => {
        const slot = slots.find(item => item.key === cropCandidate?.slot);
        if (!slot || !cropCandidate) return;
        const previewUrl = trackUrl(URL.createObjectURL(croppedFile));
        setFiles(previous => {
            const old = previous[slot.key];
            if (old?.previewUrl) revokeUrl(old.previewUrl);
            return { ...previous, [slot.key]: { file: croppedFile, previewUrl } };
        });
        revokeUrl(cropCandidate.sourceUrl);
        setCropCandidate(null);
        setNotice('پیش‌نمایش برش آماده است. برای انتشار روی «ذخیره و اعمال تصویر» بزن.');
    };

    const upload = async slot => {
        const selected = files[slot];
        if (!selected?.file) return;
        const form = new FormData();
        form.append('slot', slot);
        form.append('image', selected.file);
        form.append('alt_text', slots.find(item => item.key === slot)?.title || '');
        setBusy(slot);
        setError('');
        setNotice('');
        try {
            await api('/media', { method: 'POST', body: form });
            if (selected.previewUrl) revokeUrl(selected.previewUrl);
            setFiles(previous => {
                const next = { ...previous };
                delete next[slot];
                return next;
            });
            setNotice('تصویر ذخیره و در سایت اعمال شد.');
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
        setNotice('');
        try {
            await api('/media/' + asset.id, { method: 'DELETE' });
            setNotice('تصویر حذف شد؛ تصویر جایگزین سایت نمایش داده می‌شود.');
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
            <p>تمام تصویرهای ثابت و بنرهای صفحه خانه، منو، رزرو، ورود و گالری از اینجا مدیریت می‌شوند. عکس محصول و دسته از «منو و محصولات» قابل تغییر است.</p>
        </header>
        <div className="media-manager-intro">
            <span className="media-manager-intro-icon">✦</span>
            <div><b>یک مرحله قبل از انتشار: برش و پیش‌نمایش</b><p>هر تصویر ابتدا در کادر مخصوص همان جایگاه برش می‌خورد. پس از تأیید پیش‌نمایش، دکمه ذخیره آن را در سایت اعمال می‌کند.</p></div>
            <ActionButton disabled={busy !== ''} onClick={() => setVersion(v => v + 1)}>↻ بازخوانی</ActionButton>
        </div>
        {error && <div className="media-manager-message is-error" role="alert">{error}</div>}
        {notice && <div className="media-manager-message is-success" role="status">{notice}</div>}
        <div className="media-manager-grid">
            {slots.map(slot => {
                const asset = assets.find(item => item.slot === slot.key);
                const selected = files[slot.key];
                const preview = selected?.previewUrl || asset?.url;
                return <article className="media-asset-card" key={slot.key}>
                    <div className={'media-asset-preview is-' + slot.className} style={{ aspectRatio: slot.aspect }}>
                        {preview
                            ? <img src={preview} alt={asset?.alt_text || slot.title} />
                            : <div className="media-asset-empty"><span>＋</span><b>هنوز تصویری ثبت نشده</b><small>برای این بخش می‌توانی تصویر اختصاصی ثبت کنی.</small></div>}
                        <span className={'media-asset-status ' + (selected ? 'is-pending' : asset ? 'is-uploaded' : 'is-default')}>{selected ? 'پیش‌نمایش ذخیره‌نشده' : asset ? 'تصویر اعمال‌شده' : 'تصویر پیش‌فرض'}</span>
                    </div>
                    <div className="media-asset-body">
                        <div className="media-asset-heading"><div><h3>{slot.title}</h3><p>{slot.detail}</p></div><code>{slot.key}</code></div>
                        <div className="media-asset-guidance">نسبت تصویر: {slot.ratio} · JPG / PNG / WebP · حداکثر ۸ مگابایت</div>
                        <label className="media-file-picker"><span>انتخاب تصویر از کامپیوتر</span><input type="file" accept="image/jpeg,image/png,image/webp" onChange={event => { chooseFile(slot.key, event.target.files?.[0]); event.target.value = ''; }} /></label>
                        {selected && <div className="media-selected-file"><span>{selected.file.name}</span><b>{(selected.file.size / 1024 / 1024).toFixed(2)} MB</b></div>}
                        <div className="media-asset-actions">
                            {selected
                                ? <ActionButton kind="primary" disabled={busy !== ''} onClick={() => upload(slot.key)}>{busy === slot.key ? 'در حال ذخیره…' : 'ذخیره و اعمال تصویر'}</ActionButton>
                                : <ActionButton disabled={busy !== ''} onClick={() => setNotice('برای این جایگاه یک تصویر انتخاب کن تا برش و پیش‌نمایش آن باز شود.')}>آماده‌سازی تصویر</ActionButton>}
                            {selected && <ActionButton disabled={busy !== ''} onClick={() => { const entry=files[slot.key]; if(entry?.previewUrl) revokeUrl(entry.previewUrl); setFiles(previous=>{const next={...previous};delete next[slot.key];return next;}); }}>لغو پیش‌نمایش</ActionButton>}
                            {asset && <ActionButton kind="danger" disabled={busy !== ''} onClick={() => remove(asset)}>حذف تصویر اعمال‌شده</ActionButton>}
                        </div>
                        {asset?.updated_at && <small className="media-asset-updated">آخرین تغییر: {new Intl.DateTimeFormat('fa-IR-u-ca-persian', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(asset.updated_at))}</small>}
                    </div>
                </article>;
            })}
        </div>
        <section className="media-product-note">
            <div><b>عکس محصولات و کارت دسته‌بندی‌ها</b><p>عکس هر محصول را از فرم ویرایش محصول در «منو و محصولات» ذخیره کن. کارت دسته‌بندی به عکس اولین محصول فعال همان دسته وصل است.</p></div>
            <ActionButton onClick={() => onNavigate?.('menu')}>رفتن به منو و محصولات</ActionButton>
        </section>
        {cropCandidate && <CropDialog slot={slots.find(item => item.key === cropCandidate.slot)} file={cropCandidate.file} sourceUrl={cropCandidate.sourceUrl} onCancel={() => { revokeUrl(cropCandidate.sourceUrl); setCropCandidate(null); }} onApply={applyCrop} />}
    </div>;
}
