-- Drop view first, as Postgres complains about altering the type of
-- commission_total when directly altering Store's OrderCommissionTotalView
drop OrderCommissionTotalView;

create or replace view OrderCommissionTotalView as
select orders.id as ordernum,
	orders.item_total - orders.promotion_total as commission_total
from Orders;
