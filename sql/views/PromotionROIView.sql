create or replace view PromotionROIView as
	select PromotionCode.promotion as promotion,
		count(1) as num_orders,
		sum(promotion_total) as promotion_total,
		sum(total) as total
	from PromotionCode
		inner join Orders on Orders.promotion_code = PromotionCode.code
	group by PromotionCode.promotion;
