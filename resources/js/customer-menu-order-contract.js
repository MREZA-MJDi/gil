export function getConfirmedCustomerMenuOrder(payload) {
    const order = payload?.data;
    if (!order || String(order.number ?? '').trim() === '') return null;
    if (typeof order.status !== 'string' || order.status.trim() === '') return null;
    if (order.total === null || order.total === undefined || order.total === '') return null;

    const total = Number(order.total);
    if (!Number.isFinite(total) || total < 0) return null;

    return order;
}
