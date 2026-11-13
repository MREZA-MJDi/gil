import React, { useEffect, useState } from 'react';
import { api, unwrapData, unwrapList } from './api.js';

export default function BrandLogoSettings({ restaurantName }) {
    const [asset, setAsset] = useState(null);
    const [file, setFile] = useState(null);
    const [preview, setPreview] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [notice, setNotice] = useState('');

    useEffect(() => {
        let active = true;
        api('/media')
            .then(payload => {
                if (!active) return;
                const current = unwrapList(payload).find(item => item.slot === 'brand_logo') || null;
                setAsset(current);
                setPreview(current?.url || '');
            })
            .catch(exception => {
                if (active) setError(exception?.message || 'بارگذاری لوگوی فعلی انجام نشد.');
            });

        return () => { active = false; };
    }, []);

    useEffect(() => () => {
        if (preview.startsWith('blob:')) URL.revokeObjectURL(preview);
    }, [preview]);

    const chooseFile = selected => {
        setError('');
        setNotice('');
        if (!selected) return;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(selected.type)) {
            setError('فقط تصویر JPG، PNG یا WebP قابل انتخاب است.');
            return;
        }
        if (selected.size > 8 * 1024 * 1024) {
            setError('حجم لوگو نباید بیشتر از ۸ مگابایت باشد.');
            return;
        }

        setFile(selected);
        setPreview(URL.createObjectURL(selected));
    };

    const cancelSelection = () => {
        setFile(null);
        setPreview(asset?.url || '');
        setError('');
        setNotice('');
    };

    const upload = async () => {
        if (!file || busy) return;

        const body = new FormData();
        body.append('slot', 'brand_logo');
        body.append('image', file);
        body.append('alt_text', restaurantName || 'لوگوی مجموعه');

        setBusy(true);
        setError('');
        setNotice('');
        try {
            const response = await api('/media', { method: 'POST', body });
            const saved = unwrapData(response);
            setAsset(saved);
            setFile(null);
            setPreview(saved?.url || '');
            setNotice('لوگو ذخیره شد و در سایت اعمال شد.');
        } catch (exception) {
            setError(exception?.message || 'ذخیره لوگو انجام نشد.');
        } finally {
            setBusy(false);
        }
    };

    return <section className="panel op-card settings-brand-card">
        <div className="panel-heading">
            <div>
                <h2>لوگوی اصلی سایت</h2>
                <p>پیش‌نمایش دقیقاً با قاب دایره‌ای لوگو در صفحه عمومی هماهنگ است.</p>
            </div>
        </div>
        <div className="settings-brand-layout">
            <div className="settings-brand-preview-column">
                <div className="settings-brand-preview-frame" aria-label="پیش‌نمایش لوگو در اندازه سایت">
                    {preview
                        ? <img src={preview} alt={asset?.alt_text || restaurantName || 'لوگوی مجموعه'} />
                        : <span aria-hidden="true">{(restaurantName || 'خانه گیلاسی').slice(0, 1)}</span>}
                </div>
                <small className="settings-brand-size-note">دسکتاپ ۶۰ · موبایل ۵۲ پیکسل</small>
            </div>
            <div className="settings-brand-controls">
                <b>{file ? 'پیش‌نمایش تصویر انتخاب‌شده' : asset ? 'لوگوی فعلی سایت' : 'هنوز لوگویی بارگذاری نشده'}</b>
                <p>PNG شفاف پیشنهاد می‌شود. تصویر بدون برش و با حفظ نسبت اصلی داخل دایره قرار می‌گیرد.</p>
                <div className="settings-brand-actions">
                    <label className="settings-brand-picker">
                        <span>انتخاب لوگو از کامپیوتر</span>
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            aria-label="انتخاب فایل لوگوی سایت"
                            onChange={event => { chooseFile(event.target.files?.[0]); event.target.value = ''; }}
                        />
                    </label>
                    {file && <button type="button" className="primary-button" onClick={upload} disabled={busy}>
                        {busy ? 'در حال بارگذاری…' : 'ذخیره و اعمال لوگو'}
                    </button>}
                    {file && <button type="button" className="secondary-button" onClick={cancelSelection} disabled={busy}>لغو انتخاب</button>}
                </div>
                <small>JPG / PNG / WebP · حداکثر ۸ مگابایت</small>
            </div>
        </div>
        {error && <p className="media-manager-message is-error" role="alert">{error}</p>}
        {notice && <p className="media-manager-message is-success" role="status">{notice}</p>}
    </section>;
}
