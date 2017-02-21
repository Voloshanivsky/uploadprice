<?php
/**
 * Created by PhpStorm.
 * User: ivan
 * Date: 06.06.16
 * Time: 16:38
 */

class PriceuploadComefor extends PriceuploadUniversal
{
    /**
     * @param $name string[] код товара
     * @param $price int цена товара
     * записывает в поле $data наименование товара и его цену
     */
    /*public function add_price ($name, $price)
    {
        if ($name&&$price)
        {
            $this->data[]=array(
                'name'=>$name,
                'price'=>$price);
            //var_dump($this->data);
            //echo "test!";
        }

    }*/

    /**
     *вынимаем из прайса наименование товара и его цену
     * и записываем их в поле $data
     */
    public function parse_price()
    {

        if ($this->file1)
        {
            $dom = DOMDocument::load($this->file1);
            $rows = $dom->getElementsByTagName('Row');
            //print_r($rows);
            $row_num = 1;
            //полезная инфа начинается с 15 строки!
            //артикул позиции находится в 3 ячейке
            //цена - 6 ячейка
            foreach ($rows as $row)
            {
                if ($row_num>=15)
                {
                    $cells=$row->getElementsByTagName('Cell');
                    $cell_num=1;
                    unset($name);
                    foreach ($cells as $cell)
                    {
                        $elem=$cell->nodeValue;
                        if ($cell_num==3)
                        {
                            $name=$elem;
                        }
                        if ($cell_num==6)
                        {
                            $price=$elem;
                        }
                        $cell_num++;
                    }
                    if ($name)
                    {
                        $this->add_price($name,$price);
                    }
                }
                $row_num++;
            }
            return true;
        }
        else
        {
            $this->error_message .= "No file, no life!";
            return false;
        }
    }

    /**
     *сохраняет информацию из поля $data в базу данных сайта
     * @param $pId array массив, содержащий category_id для каждой из цен в прайсе
     * @param $priceInCurr bool флажочек, означающий что прайс в валюте
     * Добавил параметры для соответствия методу родительского класса
     * Можно в универсальной сделать проверку, если категорию не передали то обновлять как тут
     */
    public function add_db($pId = false, $priceInCurr = false)
    {
        foreach ($this->data as $d)
        {
            $d_name=$d['name'];
            //echo $d_name."<br>";
            $d_price=$d['kat0'];
            if ($priceInCurr) {
                $field_price = "pricecur";
            } else {
                $field_price = "price";
            }
            $factory_id=$this->factory_id;

            $goods = $this->findGoods($d_name, false, false);

            if ($goods)
            {
                $oldPrice = $goods['price'];
                $diff=$this->priceDif($d_price,$oldPrice);
                if ($diff > $this->warning_percent) {
                    $this->error_message .= "Цена на товар $d_name (ID={$goods['id']}) изменилась на более чем {$this->warning_percent}%, $oldPrice -> $d_price <br>";
                }
                if ($diff) {
                    // обновляем, если цена изменилась
                    $strSQL="UPDATE goods ".
                        "SET goods_$field_price=$d_price ".
                        "WHERE goods.goods_id='{$goods['id']}'";

                    $this->logForm($goods['id'], 0, $oldPrice, $d_price);
                    // $this->success_message .= $strSQL."<br>";
                    $this->db->query($strSQL);
                }
            }
            else
            {
                $this->error_message .= "$d_name - Товар не найден<br>";
                return false;
            }            

        }
        return true;
    }

    /**
     * для тестов
     * "красиво" выводим поле $data в котором лежат наименование товара и его цена
     */
    public function test_data()
    {
        ?>
        <!--<html>
        <body> -->
        <table>
            <tr>
                <th>Артикул</th>
                <th>Цена</th>
            </tr>
            <?php foreach($this->data as $row)
            {?>
                <tr>
                    <td><?php echo ($row['name']); ?></td>
                    <td><?php echo ($row['price']); ?></td>
                </tr>

            <?php } ?>

        </table>
        <!-- </body>
        </html> --> <?php
        $this->findDif();
    }

}