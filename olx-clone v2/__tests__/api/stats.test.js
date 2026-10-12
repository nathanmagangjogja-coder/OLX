import { getSellerSalesStats } from '../../lib/functions-server';
import { getDb } from '../../lib/db';

jest.mock('../../lib/db');

describe('getSellerSalesStats', () => {
  it('returns aggregated sales statistics', async () => {
    const mockDb = {
      execute: jest.fn()
        .mockResolvedValueOnce([[{ orders_count: 5, total_amount: 500000 }]])
        .mockResolvedValueOnce([[{ orders_count: 2, total_amount: 200000 }]])
        .mockResolvedValueOnce([[{ date: '2026-04-01', orders: 1, amount: 100000 }, { date: '2026-04-02', orders: 1, amount: 100000 }]])
        .mockResolvedValueOnce([[{ id: 1, title: 'Item A', sold_count: 2, revenue: 200000 }]]),
    };

    getDb.mockResolvedValue(mockDb);

    const stats = await getSellerSalesStats(42, 30);

    expect(stats.totalOrders).toBe(5);
    expect(stats.totalRevenue).toBe(500000);
    expect(stats.rangeOrders).toBe(2);
    expect(stats.rangeRevenue).toBe(200000);
    expect(stats.daily.length).toBe(2);
    expect(stats.topListings.length).toBe(1);
  });
});
