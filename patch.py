#!/usr/bin/env python3

import struct
import sys

def patch_conditional_branch(file_path, address, patch_type="always_true"):
    try:
        with open(file_path, 'r+b') as f:
            f.seek(address)

            if patch_type == "always_true":
                patch_bytes = struct.pack('<I', 0x14000001)  # B #4 (跳转到下一条指令)
                description = "总是跳转(true分支)"
            else:
                patch_bytes = struct.pack('<I', 0x1F2003D5)
                description = "总是不跳转(false分支)"

            f.write(patch_bytes)
            print(f"条件跳转已修补 at 0x{address:x}: {description}")

    except Exception as e:
        print(f"❌ 修补错误 0x{address:x}: {e}")

def nop_instruction(file_path, address, instruction_count=1):
    try:
        with open(file_path, 'r+b') as f:
            file_offset = address
            f.seek(file_offset)

            nop_bytes = struct.pack('<I', 0x1F2003D5)

            for i in range(instruction_count):
                f.write(nop_bytes)

            print(f" NOP 0x{address:x} ({instruction_count} instructions)")

    except Exception as e:
        print(f"❌ 修补出错 0x{address:x}: {e}")

def patch_string_reference(file_path, string_address, replacement_string=""):
    try:
        with open(file_path, 'r+b') as f:
            f.seek(string_address)
            
            original_data = f.read(200)
            null_pos = original_data.find(b'\x00')
            if null_pos == -1:
                print(f"找不到字符串 0x{string_address:x}")
                return
                
            original_length = null_pos
            
            # 写入新字符串
            f.seek(string_address)
            new_data = replacement_string.encode('utf-8')
            f.write(new_data)
            
            # 用null字节填充剩余空间
            remaining = original_length - len(new_data)
            if remaining > 0:
                f.write(b'\x00' * remaining)
                
            print(f"字符串已修补 0x{string_address:x}: '{replacement_string}'")
            
    except Exception as e:
        print(f"修补字符串出错 0x{string_address:x}: {e}")

def main():
    if len(sys.argv) != 2:
        print("使用说明: python3 patch.py faka.dylib")
        sys.exit(1)
        
    dylib_path = sys.argv[1]
    
    print("开始修补faka.dylib  ...")
    print("URL替换:")
    print("https://sq.onxg.top/auth.php?action=verify → https://g.apibug.com/csb.php?action=verify")
    print("https://sq.onxg.top/auth.php?action=redeem_card → https://g.apibug.com/csb.php?action=redeem_card")
    print()
    

    print("更换授权服务器URL...")
    patch_string_reference(dylib_path, 0xa3689f, "https://g.apibug.com/csb.php?action=verify")
    patch_string_reference(dylib_path, 0xa36924, "https://g.apibug.com/csb.php?action=redeem_card")


    print("移除其他其他url字符串,但是这个插件功能都是走网络的,你们可以继续写他剩下的网站")
    urls_to_remove = [
        (0xa36773, "https://wx.onxg.top/1/config.json"),
        (0xa366f8, "https://udid.onxg.top/wxid.php"),
        (0xa36717, "https://ch.onxg.top/wxid.php"),
        (0xa360f5, "https://mp.weixin.qq.com/mp/profile_ext?action=home&__biz=MzkzNzc0MDMwOQ==&scene=110#wechat_redirect"),
        (0xa35ca3, "https://ch.onxg.top/api/kami/unused?token=%@&status=%@"),
        (0xa36034, "https://udid.onch.top/api1.php?act=udid_check&udid=%@"),
        (0xa3607d, "https://udid.onxg.top/udidsttps.php?udids=%@"),
        (0xa36198, "https://ch.onxg.top/api/kami/balance?token=%@"),
        (0xa361d0, "https://ch.onxg.top/api/kami/devices?token=%@"),
        (0xa361fe, "https://ch.onxg.top/api/kami?code=%@&token=%@"),
        (0xa36644, "https://qm.qq.com/q/u1nveuy6IM"),
        (0xa36663, "https://qm.qq.com/q/M28cusQgoc"),
        (0xa36682, "https://ch.onxg.top"),
        (0xa36741, "https://ch.onxg.top/api/kami/getUsername?token=%@"),
        (0xa37068, "https://udid.onxg.top/tjzs.php?token=%@&deviceType=%@&类型=%@&版本=%@&udid=%@&域名=%@&签名=%@"),
        (0xa3769a, "https://ch.onxg.top/api/kami/unused?token=%@&设备=%@&类型=%@&版本=%@&status=%@"),
    ]

    for addr, url in urls_to_remove:
        patch_string_reference(dylib_path, addr, "")
        
    # 2. NOP掉公众号检查相关的字符串
    wechat_strings_to_remove = [
        (0xa38b90, "请关注公众号解锁所有功能\n关注完成后返回设置页再进入即可"),
        (0xa38c6c, "请关注公众号解锁更多功能"),
        (0xa38dec, "关注公众号"),
        (0xa3951a, "关注公众号解锁所有功能\n关注完成后返回设置页再进入即可\n2025.04.20 by xuegao"),
        (0xa3957c, "您的微信账号尚未授权使用此功能\n请直接输入卡密验证\n或前往公众号发送\"授权\""),
        (0xa395e6, "前往公众号"),
        (0xa39f92, "请先关注公众号"),
        (0xa39fa2, "使用此功能需要先关注公众号"),
    ]
    
    print("🔧 移除公众号检查字符串...")
    for addr, text in wechat_strings_to_remove:
        patch_string_reference(dylib_path, addr, "")

    # 3. 修补公众号关注检查 - 让isInContactList检查总是返回true
    print("🔧 修补公众号关注检查逻辑...")

    # 策略：修改条件跳转，让程序总是走"已关注公众号"的分支
    # 这样就会跳过showSubscriptionAlert，直接进入正常的授权检查流程

    functions_to_patch = [
        # checkAuthorizationStatus函数 - 修改条件跳转走true分支
        {
            'address': 0x9eed74,  # isInContactList检查的条件跳转
            'name': 'checkAuthorizationStatus',
            'description': '跳过公众号检查，直接进入微信ID授权验证'
        },

        # openProductReplySettings函数 - 修改条件跳转
        {
            'address': 0x9f7ea0,
            'name': 'openProductReplySettings',
            'description': '直接打开产品回复设置，跳过公众号检查'
        },

        # addNewCard函数 - 修改条件跳转
        {
            'address': 0x9f91a0,
            'name': 'addNewCard',
            'description': '直接添加新卡片，跳过公众号检查'
        },

        # 其他类似的函数...
        {
            'address': 0x9f9684,
            'name': 'openCardDetail',
            'description': '直接打开卡片详情'
        },

        {
            'address': 0x9f9ff0,
            'name': 'deleteLastCard',
            'description': '直接删除卡片'
        },

        {
            'address': 0xa00a8c,
            'name': 'showSortOptions',
            'description': '直接显示排序选项'
        },

        {
            'address': 0xa1a9f0,
            'name': 'balanceModeStateChanged',
            'description': '直接处理余额模式变更'
        },

        {
            'address': 0xa22e98,
            'name': 'signInSwitchChanged',
            'description': '直接处理登录开关变更'
        },
    ]

    for patch_info in functions_to_patch:
        address = patch_info['address']
        name = patch_info['name']
        description = patch_info['description']
        patch_conditional_branch(dylib_path, address, "always_true")
        print(f"修补 {name}: {description}")

    print()
    print("✅ 修补完成!")

if __name__ == "__main__":
    main()
