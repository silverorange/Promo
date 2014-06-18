create or replace view OrderCommissionTotalView as
select orders.id as ordernum,
	cast((orders.item_total - orders.promotion_total) as numeric(11,2))
		as commission_total
from Orders;
