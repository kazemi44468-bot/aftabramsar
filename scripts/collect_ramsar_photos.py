#!/usr/bin/env python3
"""Download up to 50 real Ramsar photos from Wikimedia Commons and save attribution."""
import json, os, re, time, urllib.parse, urllib.request
ROOT=os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT=os.path.join(ROOT,"assets","images")
os.makedirs(OUT,exist_ok=True)
API="https://commons.wikimedia.org/w/api.php"
HEAD={"User-Agent":"AftabRamsarWebsite/1.0 (website photo collection)"}
def api(params):
    url=API+"?"+urllib.parse.urlencode(params)
    req=urllib.request.Request(url,headers=HEAD)
    with urllib.request.urlopen(req,timeout=40) as res: return json.load(res)
def clean(s): return re.sub(r"<[^>]+>"," ",s or "").replace("&quot;",'"').replace("&amp;","&").strip()
def main():
    found={}
    categories=["Category:Ramsar","Category:Geography of Ramsar","Category:Nature of Ramsar","Category:Buildings in Ramsar","Category:Tourism in Ramsar","Category:Transport in Ramsar"]
    for cat in categories:
        cont={}
        while len(found)<50:
            p={"action":"query","format":"json","generator":"categorymembers","gcmtitle":cat,"gcmtype":"file","gcmlimit":"50","prop":"imageinfo","iiprop":"url|extmetadata","iiurlwidth":"1600"}
            p.update(cont)
            try: data=api(p)
            except Exception as e: print("Category skipped:",cat,e); break
            for page in data.get("query",{}).get("pages",{}).values():
                info=(page.get("imageinfo") or [{}])[0]; title=page.get("title","")
                url=info.get("thumburl") or info.get("url")
                if url and title.lower().endswith((".jpg",".jpeg")):
                    meta=info.get("extmetadata",{})
                    found.setdefault(title,{"url":url,"description":clean(meta.get("ImageDescription",{}).get("value","")),"artist":clean(meta.get("Artist",{}).get("value","")),"license":clean(meta.get("LicenseShortName",{}).get("value","")),"license_url":meta.get("LicenseUrl",{}).get("value",""),"source_page":"https://commons.wikimedia.org/wiki/"+urllib.parse.quote(title.replace("File:","File:"),safe=":/")})
            if "continue" not in data: break
            cont=data["continue"]; time.sleep(.15)
        if len(found)>=50: break
    if len(found)<50:
        for term in ["Ramsar Iran","Ramsar telecabin","Ramsar Caspian coast","Ramsar forest","Javaherdeh Ramsar"]:
            if len(found)>=50: break
            try:
                data=api({"action":"query","format":"json","generator":"search","gsrsearch":term,"gsrnamespace":6,"gsrlimit":50,"prop":"imageinfo","iiprop":"url|extmetadata","iiurlwidth":1600})
                for page in data.get("query",{}).get("pages",{}).values():
                    info=(page.get("imageinfo") or [{}])[0]; title=page.get("title",""); url=info.get("thumburl") or info.get("url")
                    if url and title.lower().endswith((".jpg",".jpeg")):
                        meta=info.get("extmetadata",{})
                        found.setdefault(title,{"url":url,"description":clean(meta.get("ImageDescription",{}).get("value","")),"artist":clean(meta.get("Artist",{}).get("value","")),"license":clean(meta.get("LicenseShortName",{}).get("value","")),"license_url":meta.get("LicenseUrl",{}).get("value",""),"source_page":"https://commons.wikimedia.org/wiki/"+urllib.parse.quote(title,safe=":/")})
            except Exception as e: print("Search skipped:",e)
    manifest=[]
    for title,item in list(found.items())[:50]:
        target=os.path.join(OUT,f"ramsar-{len(manifest)+1:02d}.jpg")
        try:
            req=urllib.request.Request(item["url"],headers=HEAD)
            with urllib.request.urlopen(req,timeout=60) as res: data=res.read()
            if len(data)<6000: raise ValueError("image too small")
            with open(target,"wb") as f: f.write(data)
            manifest.append({"file":os.path.basename(target),"title":title,"source_page":item["source_page"],"artist":item["artist"],"license":item["license"],"license_url":item["license_url"],"description":item["description"]})
            print(f"Downloaded {i}/50: {title}")
        except Exception as e: print("Failed:",title,e)
        time.sleep(.12)
    with open(os.path.join(OUT,"sources.json"),"w",encoding="utf-8") as f: json.dump(manifest,f,ensure_ascii=False,indent=2)
    if len(manifest)<50: print(f"NOTICE: downloaded {len(manifest)} photos, not 50; check Wikimedia category/API availability.")
    else: print("Done: 50 images saved under images/.")
if __name__=="__main__": main()
