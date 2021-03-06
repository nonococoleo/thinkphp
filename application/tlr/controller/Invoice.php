<?php

namespace app\tlr\controller;

use app\tlr\model\InvoiceModel;
use app\tlr\model\LogModel;
use app\tlr\model\PayModel;
use gmars\rbac\Rbac;
use think\Controller;
use think\Request;

class Invoice extends Controller
{
//首页显示所有发票情况
    public function index(Request $request)
    {
        $rbacObj = new Rbac();
        if(!$rbacObj->can($request->path())) {
            $this->error("没有权限", "index/index", null, 3);
            exit();
        }
        $Invoice = new InvoiceModel();
        $state = $request->param('state');
        if ($state) {
            $query = ["state" => $state];
            $invoice = $Invoice->alias("i")->join("pay p", "p.iid=i.iid")->where("i.delflag", "=", 0)->where("state", "=", "$state")->field("i.iid,i.fee,title,number,i.date,state,p.pid,i.memo,i.uid")->paginate(10, false, ['type' => 'bootstrap']);
        } else {
            $query = ["state" => 0];
            $invoice = $Invoice->alias("i")->join("pay p", "p.iid=i.iid")->where("i.delflag", "=", 0)->field("i.iid,i.fee,title,number,i.date,state,p.pid,i.memo,i.uid")->paginate(10, false, ['type' => 'bootstrap']);
        }
        if ($invoice->count() > 0) {
            $page = $invoice->render();
            $data = array_merge(["invoice" => $invoice, "page" => $page], $query);
            $this->assign($data);
            $htmls = $this->fetch("index");
            return $htmls;
        } else {
            $this->error("查无记录", null, null, 1);
            return null;
        }
    }

    //根据ID获取发票
    public function invById(Request $request)
    {
        $rbacObj = new Rbac();
        if(!$rbacObj->can($request->path())) {
            $this->error("没有权限", "Invoice/index", null, 1);
            exit();
        }
        $Invoice = new InvoiceModel();
        try {
            $invoice = $Invoice->where('iid', $_POST['iid'])->find();
        } catch (\Exception $e) {
            echo json_encode(array("success" => false, 'msg' => "服务器繁忙，请稍后重试"));
        }
        echo json_encode(array('inv' => $invoice, 'success' => true));
    }

    //添加发票信息接口
    public function add(Request $request)
    {
        $rbacObj = new Rbac();
        if(!$rbacObj->can($request->path())) {
            $this->error("没有权限", "Invoice/index", null, 1);
            exit();
        }
        $Invoice = new InvoiceModel();
        $Log = new LogModel();
        $Pay = new PayModel();
        foreach ($_POST as $key => $value)
            if ($value == "")
                $_POST[$key] = null;
        try {
            $Invoice->allowField("fee,title,number,date,state,uid,memo")->save($_POST);
            $Pay->save(["iid" => $Invoice->getLastInsID()], ['pid' => $request->param("pid")]);
            $Log->save(["uid" => session('uid'), "action" => $Invoice->getlastsql(), "time" => date("Y-m-d H:i:s")]);
        } catch (\Exception $e) {
            $this->error("添加失败", null, null, 1);
        }
        $this->success("添加成功", null, null, 1);
        return null;
    }

    //删除发票信息接口
    public function del(Request $request)
    {
        $rbacObj = new Rbac();
        if(!$rbacObj->can($request->path())) {
            $this->error("没有权限", "Invoice/index", null, 1);
            exit();
        }
        try {
            $Invoice = new InvoiceModel();
            $Log = new LogModel();
            $Invoice->save(["delflag" => 1], ['iid' => $request->param("iid")]);
            $Log->save(["uid" => session('uid'), "action" => $Invoice->getlastsql(), "time" => date("Y-m-d H:i:s")]);
        } catch (\Exception $e) {
            $this->error("删除失败", null, null, 1);
        }
        $this->success("删除成功", null, null, 1);
        return null;
    }

    //修改发票信息接口
    public function mod(Request $request)
    {
        $rbacObj = new Rbac();
        if(!$rbacObj->can($request->path())) {
            $this->error("没有权限", "Invoice/index", null, 1);
            exit();
        }
        $Invoice = new InvoiceModel();
        $Log = new LogModel();
        foreach ($_POST as $key => $value)
            if ($value == "")
                $_POST[$key] = null;
        try {
            $Invoice->allowField(['fee', 'title', 'number', 'date', 'state', 'memo'])->save($_POST, ['iid' => $request->param("iid")]);
            $Log->save(["uid" => session('uid'), "action" => $Invoice->getlastsql(), "time" => date("Y-m-d H:i:s")]);
        } catch (\Exception $e) {
            $this->error("修改失败", null, null, 1);
        }
        $this->success("修改成功", null, null, 1);
        return null;
    }
}
