import { getSubcategories } from '../../lib/functions-server';
import { getDb } from '../../lib/db';

jest.mock('../../lib/db');

describe('getSubcategories', () => {
  it('returns subcategories for a given category_id', async () => {
    const mockSubs = [
      { id: 1, name: 'Sub 1' },
      { id: 2, name: 'Sub 2' },
    ];
    const mockDb = {
      execute: jest.fn().mockResolvedValue([mockSubs]),
    };
    getDb.mockResolvedValue(mockDb);

    const rows = await getSubcategories('1');

    expect(rows).toEqual(mockSubs);
    expect(mockDb.execute).toHaveBeenCalledWith(
      "SELECT * FROM subcategories WHERE category_id = ? AND is_active = 1 ORDER BY sort_order",
      ['1']
    );
  });

  it('returns empty array when no category_id provided', async () => {
    const mockDb = {
      execute: jest.fn().mockResolvedValue([[]]),
    };
    getDb.mockResolvedValue(mockDb);

    const rows = await getSubcategories(undefined);

    expect(rows).toEqual([]);
  });
});
