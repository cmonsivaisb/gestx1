<?php

class reporte {
	public function ventasPeriodos ($context) {
		$id_usuario = $context->id_usuario;

		 $ventas = $context->db->query("SELECT DATE_FORMAT(fecha_inicio, '%m') AS month, 
			DATE_FORMAT(fecha_inicio, '%Y') AS year, 
			COUNT(id_pedido) AS qty,
			SUM(total) AS amount
			FROM pedidos
			WHERE pedidos.p_p = 1
			AND pedidos.no_contabilizar != 1
			AND pedidos.sititec != 1
			AND pedidos.id_vendedor = '$id_usuario' 
			GROUP BY DATE_FORMAT(fecha_inicio, '%m-%Y')
			ORDER BY year DESC, month DESC;");

		foreach($ventas as $venta) { 
			$month = $venta['month'];
			$year = $venta['year'];
			$venta['quotes'] = $context->db->query("SELECT COUNT(id_pedido) count FROM pedidos WHERE id_vendedor = '$id_usuario' 
				AND no_contabilizar != 1
				AND sititec != 1
				AND DATE_FORMAT(fecha_inicio, '%m') = '$month' 
				AND DATE_FORMAT(fecha_inicio, '%Y') = '$year';")[0]['count'];

			$venta['amountPaid'] = $context->db->query("SELECT SUM(total) AS amountPaid
			FROM pedidos_notas
			WHERE id_pedido IN (
				SELECT id_pedido FROM pedidos WHERE pedidos.p_p = 1 
				AND pedidos.p_f = 1
				AND pedidos.no_contabilizar != 1
				AND pedidos.sititec != 1
				AND pedidos.id_vendedor = '$id_usuario' 
				AND DATE_FORMAT(fecha_inicio, '%m') = '$month'
				AND DATE_FORMAT(fecha_inicio, '%Y') = '$year'
			);")[0]['amountPaid'];

			$context->result['ventas'][] = $venta;
		}

		$orders = $context->db->query("SELECT COUNT(id_pedido) AS qty, SUM(total) AS amount
			FROM pedidos
			WHERE p_p = 1
			AND no_contabilizar != 1
			AND sititec != 1
			AND id_vendedor = '$id_usuario' 
			AND MONTH(fecha_inicio) = MONTH(CURRENT_DATE())
			AND YEAR(fecha_inicio) = YEAR(CURRENT_DATE());")[0];

		$context->result['quotes'] = $context->db->query("SELECT COUNT(id_pedido) AS qty
			FROM pedidos
			WHERE no_contabilizar != 1
			AND sititec != 1
			AND id_vendedor = '$id_usuario' 
			AND MONTH(fecha_inicio) = MONTH(CURRENT_DATE())
			AND YEAR(fecha_inicio) = YEAR(CURRENT_DATE());")[0]['qty'];

		$context->result['pendingQuotes'] = $context->db->query("SELECT COUNT(id_pedido) AS qty
			FROM pedidos
			WHERE no_contabilizar != 1
			AND sititec != 1
			AND id_vendedor = '$id_usuario' 
			AND fecha_envio_cotizacion IS NULL
			AND MONTH(fecha_inicio) = MONTH(CURRENT_DATE())
			AND YEAR(fecha_inicio) = YEAR(CURRENT_DATE());")[0]['qty'];
		
		$context->result['orders'] = $orders['qty'];
		$context->result['salesAmount'] = $orders['amount'];

	}

	public function ventasHistorico ($context) {
		$context->result['ingreso'] = $context->db->query("SELECT DATE_FORMAT(fecha_nota, '%m') AS month, 
			DATE_FORMAT(fecha_nota, '%Y') AS year, SUM(total) AS amount
			FROM pedidos_notas
			WHERE id_pedido IN (
				SELECT id_pedido FROM pedidos WHERE pedidos.p_p = 1 
				AND pedidos.p_f = 1
				AND pedidos.no_contabilizar != 1
				AND pedidos.sititec != 1
			)
			GROUP BY DATE_FORMAT(fecha_nota, '%m-%Y')
			ORDER BY year ASC, month ASC;");

		$context->result['ventas'] = $context->db->query("SELECT DATE_FORMAT(fecha_inicio, '%m') AS month, 
			DATE_FORMAT(fecha_inicio, '%Y') AS year, SUM(total) AS amount
			FROM pedidos
			WHERE pedidos.p_p = 1
			AND pedidos.no_contabilizar != 1
			AND pedidos.sititec != 1
			GROUP BY DATE_FORMAT(fecha_inicio, '%m-%Y')
			ORDER BY year ASC, month ASC;");

		$salesByYear = array ();
		$years = array("2016","2017","2018","2019","2020");
		foreach($years as $year) {
			$startDate = date('Y-m-d',strtotime("first day of January {$year}"));
			$endDate = date('Y-m-d',strtotime("last day of December {$year}"));
			$months = $context->db->query("SELECT DATE_FORMAT(fecha_inicio, '%M') AS month, 
				SUM(total) AS amount
				FROM pedidos
				WHERE pedidos.p_p = 1
				AND pedidos.no_contabilizar != 1
				AND pedidos.sititec != 1
				AND pedidos.fecha_inicio BETWEEN '$startDate' AND '$endDate'
				GROUP BY DATE_FORMAT(fecha_inicio, '%m')
				ORDER BY DATE_FORMAT(fecha_inicio, '%m') ASC;");
			$total = $context->db->query("SELECT SUM(total) AS amount
				FROM pedidos
				WHERE pedidos.p_p = 1
				AND pedidos.no_contabilizar != 1
				AND pedidos.sititec != 1
				AND pedidos.fecha_inicio BETWEEN '$startDate' AND '$endDate';")[0];
			$salesByYear[] = array("yearName"=>$year, "months"=>$months, "total"=>$total['amount']);
		}
		$context->result['salesByYear'] = $salesByYear;
	}

}
?>
